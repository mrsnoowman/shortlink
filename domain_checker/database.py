"""
Database connection and operations
"""
import aiomysql
import logging
from typing import List, Dict, Optional
from config import DB_CONFIG

logger = logging.getLogger(__name__)


class Database:
    """Database connection manager"""
    
    def __init__(self):
        self.pool: Optional[aiomysql.Pool] = None
    
    async def connect(self):
        """Create database connection pool"""
        try:
            self.pool = await aiomysql.create_pool(
                host=DB_CONFIG['host'],
                port=DB_CONFIG['port'],
                user=DB_CONFIG['user'],
                password=DB_CONFIG['password'],
                db=DB_CONFIG['db'],
                charset=DB_CONFIG['charset'],
                autocommit=True,
                minsize=5,
                maxsize=20
            )
            logger.info("Database connection pool created successfully")
        except Exception as e:
            logger.error(f"Failed to create database pool: {e}")
            raise
    
    async def close(self):
        """Close database connection pool"""
        if self.pool:
            self.pool.close()
            await self.pool.wait_closed()
            logger.info("Database connection pool closed")
    
    async def fetch_all_target_urls(self) -> List[Dict]:
        """
        Fetch all target URLs with their shortlink and user info
        Returns: List of dicts with id, url, shortlink_id, user_id, domain, old_is_blocked
        """
        async with self.pool.acquire() as conn:
            async with conn.cursor(aiomysql.DictCursor) as cursor:
                query = """
                    SELECT 
                        tu.id,
                        tu.url,
                        tu.shortlink_id,
                        tu.is_blocked,
                        s.user_id,
                        s.short_code
                    FROM target_urls tu
                    INNER JOIN shortlinks s ON tu.shortlink_id = s.id
                    ORDER BY tu.id
                """
                await cursor.execute(query)
                results = await cursor.fetchall()
                
                # Extract domain from URL and store old status
                for result in results:
                    domain = self._extract_domain(result['url'])
                    result['domain'] = domain
                    result['old_is_blocked'] = result['is_blocked']  # Store old status for comparison
                
                logger.info(f"Fetched {len(results)} target URLs from database")
                return list(results)
    
    def _extract_domain(self, url: str) -> Optional[str]:
        """
        Extract domain from URL
        Example: https://example.com/path -> example.com
        """
        if not url:
            return None
        
        # Remove protocol
        url = url.replace('http://', '').replace('https://', '')
        
        # Remove path and query
        url = url.split('/')[0]
        url = url.split('?')[0]
        
        # Remove port if exists
        url = url.split(':')[0]
        
        return url.strip()
    
    async def bulk_update_blocked_status(self, updates: List[Dict]):
        """
        Bulk update is_blocked status for target URLs
        updates: List of dicts with {'id': int, 'is_blocked': bool}
        Also handles auto-switching primary if primary becomes blocked
        """
        if not updates:
            return
        
        async with self.pool.acquire() as conn:
            async with conn.cursor() as cursor:
                # Prepare bulk update query
                values = []
                blocked_primary_ids = []  # Track which primary targets became blocked
                
                for update in updates:
                    values.append((update['is_blocked'], update['id']))
                    
                    # Check if this is a primary target that's being blocked
                    if update['is_blocked']:
                        check_primary_query = """
                            SELECT is_primary, shortlink_id 
                            FROM target_urls 
                            WHERE id = %s AND is_primary = 1
                        """
                        await cursor.execute(check_primary_query, (update['id'],))
                        result = await cursor.fetchone()
                        if result:
                            blocked_primary_ids.append({
                                'id': update['id'],
                                'shortlink_id': result[1]
                            })
                
                query = """
                    UPDATE target_urls 
                    SET is_blocked = %s, updated_at = NOW()
                    WHERE id = %s
                """
                
                await cursor.executemany(query, values)
                affected = cursor.rowcount
                
                logger.info(f"Updated {affected} target URLs in database")
                
                # Log status changes for Telegram notifications
                await self._log_target_url_changes(updates)
                
                # Auto-switch primary if primary became blocked
                if blocked_primary_ids:
                    logger.info(f"Checking {len(blocked_primary_ids)} blocked primary targets for auto-switch...")
                    for blocked_primary in blocked_primary_ids:
                        shortlink_id = blocked_primary['shortlink_id']
                        
                        # Find first non-blocked target for this shortlink
                        find_new_primary_query = """
                            SELECT id 
                            FROM target_urls 
                            WHERE shortlink_id = %s 
                            AND is_blocked = 0 
                            AND id != %s
                            ORDER BY id ASC 
                            LIMIT 1
                        """
                        await cursor.execute(find_new_primary_query, (shortlink_id, blocked_primary['id']))
                        new_primary = await cursor.fetchone()
                        
                        if new_primary:
                            # Unset old primary and set new primary
                            switch_query = """
                                UPDATE target_urls
                                SET is_primary = CASE 
                                    WHEN id = %s THEN 1
                                    WHEN id = %s THEN 0
                                    ELSE is_primary
                                END,
                                updated_at = NOW()
                                WHERE id = %s OR id = %s
                            """
                            await cursor.execute(switch_query, (
                                new_primary[0],  # New primary ID
                                blocked_primary['id'],  # Old primary ID
                                new_primary[0],
                                blocked_primary['id']
                            ))
                            logger.info(f"Auto-switched primary for shortlink {shortlink_id}: {blocked_primary['id']} -> {new_primary[0]}")
                        else:
                            # No non-blocked target found, just unset primary
                            unset_query = """
                                UPDATE target_urls
                                SET is_primary = 0, updated_at = NOW()
                                WHERE id = %s
                            """
                            await cursor.execute(unset_query, (blocked_primary['id'],))
                            logger.warning(f"No active target found for shortlink {shortlink_id}, unset primary")
    
    async def fetch_all_domain_checks(self) -> List[Dict]:
        """
        Fetch all domain checks
        Returns: List of dicts with id, domain, user_id, is_blocked
        """
        async with self.pool.acquire() as conn:
            async with conn.cursor(aiomysql.DictCursor) as cursor:
                query = """
                    SELECT 
                        id,
                        domain,
                        user_id,
                        is_blocked
                    FROM domain_checks
                    ORDER BY id
                """
                await cursor.execute(query)
                results = await cursor.fetchall()
                
                logger.info(f"Fetched {len(results)} domain checks from database")
                return list(results)
    
    async def bulk_update_domain_check_status(self, updates: List[Dict]):
        """
        Bulk update is_blocked status for domain checks
        updates: List of dicts with {'id': int, 'is_blocked': bool, 'old_is_blocked': bool, 'user_id': int, 'domain': str}
        Also logs status changes for Telegram notifications
        """
        if not updates:
            return
        
        async with self.pool.acquire() as conn:
            async with conn.cursor() as cursor:
                # Prepare bulk update query
                values = []
                for update in updates:
                    values.append((update['is_blocked'], update['id']))
                
                query = """
                    UPDATE domain_checks 
                    SET is_blocked = %s, updated_at = NOW()
                    WHERE id = %s
                """
                
                await cursor.executemany(query, values)
                affected = cursor.rowcount
                
                logger.info(f"Updated {affected} domain checks in database")
                
                # Log status changes for Telegram notifications
                await self._log_domain_check_changes(updates)
    
    async def _log_domain_check_changes(self, updates: List[Dict]):
        """
        Log status changes to domain_status_changes table for Telegram notifications
        updates: List of dicts with {'id': int, 'is_blocked': bool, 'old_is_blocked': bool, 'user_id': int, 'domain': str}
        """
        if not updates:
            return
        
        async with self.pool.acquire() as conn:
            async with conn.cursor() as cursor:
                changes_to_log = []
                
                for update in updates:
                    # Only log if status actually changed
                    if 'old_is_blocked' not in update or update['old_is_blocked'] == update['is_blocked']:
                        continue
                    
                    # Get domain check info if not provided
                    if 'user_id' not in update or 'domain' not in update:
                        info_query = """
                            SELECT user_id, domain
                            FROM domain_checks
                            WHERE id = %s
                        """
                        await cursor.execute(info_query, (update['id'],))
                        info = await cursor.fetchone()
                        
                        if not info:
                            continue
                        
                        update['user_id'] = info[0] if 'user_id' not in update else update['user_id']
                        update['domain'] = info[1] if 'domain' not in update else update['domain']
                    
                    changes_to_log.append(update)
                
                if not changes_to_log:
                    return
                
                # Bulk insert status changes
                insert_values = []
                for change in changes_to_log:
                    insert_values.append((
                        change['user_id'],
                        None,  # shortlink_id
                        None,  # target_url_id
                        change['id'],  # domain_check_id
                        change.get('domain'),
                        None,  # url
                        1 if change['old_is_blocked'] else 0,
                        1 if change['is_blocked'] else 0,
                        'domain_check',
                    ))
                
                insert_query = """
                    INSERT INTO domain_status_changes 
                    (user_id, shortlink_id, target_url_id, domain_check_id, domain, url, old_status, new_status, change_type, notified, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, 0, NOW(), NOW())
                """
                await cursor.executemany(insert_query, insert_values)
                
                logger.info(f"Logged {len(changes_to_log)} domain check status changes for notifications")
    
    async def get_shortlink_stats(self) -> Dict:
        """Get statistics about shortlinks and target URLs"""
        async with self.pool.acquire() as conn:
            async with conn.cursor(aiomysql.DictCursor) as cursor:
                query = """
                    SELECT 
                        COUNT(DISTINCT s.id) as total_shortlinks,
                        COUNT(DISTINCT s.user_id) as total_users,
                        COUNT(tu.id) as total_target_urls,
                        SUM(CASE WHEN tu.is_blocked = 1 THEN 1 ELSE 0 END) as blocked_urls,
                        SUM(CASE WHEN tu.is_blocked = 0 THEN 1 ELSE 0 END) as active_urls
                    FROM shortlinks s
                    LEFT JOIN target_urls tu ON s.id = tu.shortlink_id
                """
                await cursor.execute(query)
                result = await cursor.fetchone()
                return result
    
    async def _log_target_url_changes(self, updates: List[Dict]):
        """
        Log status changes to domain_status_changes table for Telegram notifications
        updates: List of dicts with {'id': int, 'is_blocked': bool, 'old_is_blocked': bool, 'shortlink_id': int, 'user_id': int, 'url': str, 'domain': str}
        """
        if not updates:
            return
        
        async with self.pool.acquire() as conn:
            async with conn.cursor() as cursor:
                changes_to_log = []
                
                for update in updates:
                    # Only log if status actually changed
                    if 'old_is_blocked' not in update or update['old_is_blocked'] == update['is_blocked']:
                        continue
                    
                    # Get shortlink and user info if not provided
                    if 'shortlink_id' not in update or 'user_id' not in update:
                        info_query = """
                            SELECT tu.shortlink_id, tu.url, s.user_id
                            FROM target_urls tu
                            INNER JOIN shortlinks s ON tu.shortlink_id = s.id
                            WHERE tu.id = %s
                        """
                        await cursor.execute(info_query, (update['id'],))
                        info = await cursor.fetchone()
                        
                        if not info:
                            continue
                        
                        update['shortlink_id'] = info[0]
                        update['url'] = info[1] if 'url' not in update else update['url']
                        update['user_id'] = info[2]
                    
                    if 'domain' not in update:
                        update['domain'] = self._extract_domain(update.get('url', ''))
                    
                    changes_to_log.append(update)
                
                if not changes_to_log:
                    return
                
                # Bulk insert status changes
                insert_values = []
                for change in changes_to_log:
                    insert_values.append((
                        change['user_id'],
                        change.get('shortlink_id'),
                        change['id'],
                        None,  # domain_check_id
                        change.get('domain'),
                        change.get('url'),
                        1 if change['old_is_blocked'] else 0,
                        1 if change['is_blocked'] else 0,
                        'target_url',
                    ))
                
                insert_query = """
                    INSERT INTO domain_status_changes 
                    (user_id, shortlink_id, target_url_id, domain_check_id, domain, url, old_status, new_status, change_type, notified, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, 0, NOW(), NOW())
                """
                await cursor.executemany(insert_query, insert_values)
                
                logger.info(f"Logged {len(changes_to_log)} target URL status changes for notifications")
    
    async def get_domain_check_stats(self) -> Dict:
        """Get statistics about domain checks"""
        async with self.pool.acquire() as conn:
            async with conn.cursor(aiomysql.DictCursor) as cursor:
                query = """
                    SELECT 
                        COUNT(*) as total_domain_checks,
                        COUNT(DISTINCT user_id) as total_users,
                        SUM(CASE WHEN is_blocked = 1 THEN 1 ELSE 0 END) as blocked_domains,
                        SUM(CASE WHEN is_blocked = 0 THEN 1 ELSE 0 END) as active_domains
                    FROM domain_checks
                """
                await cursor.execute(query)
                result = await cursor.fetchone()
                return result
        """
        Log status changes to domain_status_changes table for Telegram notifications
        updates: List of dicts with {'id': int, 'is_blocked': bool, 'old_is_blocked': bool, 'shortlink_id': int, 'user_id': int, 'url': str, 'domain': str}
        """
        if not updates:
            return
        
        async with self.pool.acquire() as conn:
            async with conn.cursor() as cursor:
                changes_to_log = []
                
                for update in updates:
                    # Only log if status actually changed
                    if 'old_is_blocked' not in update or update['old_is_blocked'] == update['is_blocked']:
                        continue
                    
                    # Get shortlink and user info if not provided
                    if 'shortlink_id' not in update or 'user_id' not in update:
                        info_query = """
                            SELECT tu.shortlink_id, tu.url, s.user_id
                            FROM target_urls tu
                            INNER JOIN shortlinks s ON tu.shortlink_id = s.id
                            WHERE tu.id = %s
                        """
                        await cursor.execute(info_query, (update['id'],))
                        info = await cursor.fetchone()
                        
                        if not info:
                            continue
                        
                        update['shortlink_id'] = info[0]
                        update['url'] = info[1] if 'url' not in update else update['url']
                        update['user_id'] = info[2]
                    
                    if 'domain' not in update:
                        update['domain'] = self._extract_domain(update.get('url', ''))
                    
                    changes_to_log.append(update)
                
                if not changes_to_log:
                    return
                
                # Bulk insert status changes
                insert_values = []
                for change in changes_to_log:
                    insert_values.append((
                        change['user_id'],
                        change.get('shortlink_id'),
                        change['id'],
                        None,  # domain_check_id
                        change.get('domain'),
                        change.get('url'),
                        1 if change['old_is_blocked'] else 0,
                        1 if change['is_blocked'] else 0,
                        'target_url',
                    ))
                
                insert_query = """
                    INSERT INTO domain_status_changes 
                    (user_id, shortlink_id, target_url_id, domain_check_id, domain, url, old_status, new_status, change_type, notified, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, 0, NOW(), NOW())
                """
                await cursor.executemany(insert_query, insert_values)
                
                logger.info(f"Logged {len(changes_to_log)} target URL status changes for notifications")

