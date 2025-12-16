"""
Main script for domain checking
"""
import asyncio
import logging
import sys
from datetime import datetime
from database import Database
from checker import DomainChecker
from config import LOG_LEVEL

# Map log level string to logging constant
LOG_LEVELS = {
    'DEBUG': logging.DEBUG,
    'INFO': logging.INFO,
    'WARNING': logging.WARNING,
    'ERROR': logging.ERROR,
    'CRITICAL': logging.CRITICAL
}

# Get log level, default to INFO if invalid
log_level = LOG_LEVELS.get(LOG_LEVEL.upper() if isinstance(LOG_LEVEL, str) else 'INFO', logging.INFO)

# Configure logging
logging.basicConfig(
    level=log_level,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('domain_checker.log'),
        logging.StreamHandler(sys.stdout)
    ]
)

logger = logging.getLogger(__name__)


async def main():
    """Main function"""
    start_time = datetime.now()
    logger.info("=" * 60)
    logger.info("Domain Checker Started")
    logger.info("=" * 60)
    
    db = Database()
    checker = DomainChecker()
    
    try:
        # Initialize connections
        await db.connect()
        await checker.initialize()
        
        # Get statistics before checking
        stats_before = await db.get_shortlink_stats()
        dc_stats_before = await db.get_domain_check_stats()
        
        logger.info(f"Statistics before check:")
        logger.info(f"  Shortlinks:")
        logger.info(f"    - Total Shortlinks: {stats_before['total_shortlinks']}")
        logger.info(f"    - Total Users: {stats_before['total_users']}")
        logger.info(f"    - Total Target URLs: {stats_before['total_target_urls']}")
        logger.info(f"    - Active URLs: {stats_before['active_urls']}")
        logger.info(f"    - Blocked URLs: {stats_before['blocked_urls']}")
        logger.info(f"  Domain Checks:")
        logger.info(f"    - Total Domain Checks: {dc_stats_before['total_domain_checks']}")
        logger.info(f"    - Total Users: {dc_stats_before['total_users']}")
        logger.info(f"    - Active Domains: {dc_stats_before['active_domains']}")
        logger.info(f"    - Blocked Domains: {dc_stats_before['blocked_domains']}")
        
        # Fetch all target URLs
        logger.info("Fetching target URLs from database...")
        target_urls = await db.fetch_all_target_urls()
        
        # Fetch all domain checks
        logger.info("Fetching domain checks from database...")
        domain_checks = await db.fetch_all_domain_checks()
        
        if not target_urls and not domain_checks:
            logger.warning("No target URLs or domain checks found in database")
            return
        
        # Combine all domains to check (for efficiency)
        all_domains_to_check = []
        id_to_type = {}  # Map ID to type for later processing
        
        # Prepare target URLs for checking
        if target_urls:
            for tu in target_urls:
                domain = tu.get('domain')
                if domain:
                    item_id = tu['id']
                    all_domains_to_check.append({
                        'id': item_id,
                        'domain': domain
                    })
                    id_to_type[item_id] = {'type': 'target_url', 'data': tu}
        
        # Prepare domain checks for checking
        if domain_checks:
            for dc in domain_checks:
                domain = dc.get('domain')
                if domain:
                    item_id = dc['id']
                    all_domains_to_check.append({
                        'id': item_id,
                        'domain': domain
                    })
                    id_to_type[item_id] = {'type': 'domain_check', 'data': dc}
        
        # Check all domains
        logger.info(f"Starting domain checking process for {len(all_domains_to_check)} domains...")
        blocked_status = await checker.check_all_domains(all_domains_to_check)
        
        # Prepare updates for target URLs and domain checks
        target_url_updates = []
        domain_check_updates = []
        
        for item_id, is_blocked in blocked_status.items():
            if item_id not in id_to_type:
                continue
            
            item_info = id_to_type[item_id]
            item_type = item_info['type']
            item_data = item_info['data']
            
            if item_type == 'target_url':
                old_status = item_data.get('is_blocked', False)
                if is_blocked != old_status:
                    target_url_updates.append({
                        'id': item_id,
                        'is_blocked': is_blocked,
                        'old_is_blocked': old_status,
                        'shortlink_id': item_data.get('shortlink_id'),
                        'user_id': item_data.get('user_id'),
                        'url': item_data.get('url'),
                        'domain': item_data.get('domain'),
                    })
                    logger.debug(f"Target URL {item_id} ({item_data.get('url', 'N/A')}): {old_status} -> {is_blocked}")
            
            elif item_type == 'domain_check':
                old_status = item_data.get('is_blocked', False)
                if is_blocked != old_status:
                    domain_check_updates.append({
                        'id': item_id,
                        'is_blocked': is_blocked,
                        'old_is_blocked': old_status,
                        'user_id': item_data.get('user_id'),
                        'domain': item_data.get('domain'),
                    })
                    logger.debug(f"Domain Check {item_id} ({item_data.get('domain', 'N/A')}): {old_status} -> {is_blocked}")
        
        # Bulk update database
        total_updates = 0
        if target_url_updates:
            logger.info(f"Updating {len(target_url_updates)} target URLs in database...")
            await db.bulk_update_blocked_status(target_url_updates)
            total_updates += len(target_url_updates)
        
        if domain_check_updates:
            logger.info(f"Updating {len(domain_check_updates)} domain checks in database...")
            await db.bulk_update_domain_check_status(domain_check_updates)
            total_updates += len(domain_check_updates)
        
        if total_updates == 0:
            logger.info("No status changes detected, database not updated")
        
        # Get statistics after checking
        stats_after = await db.get_shortlink_stats()
        dc_stats_after = await db.get_domain_check_stats()
        
        logger.info(f"Statistics after check:")
        logger.info(f"  Shortlinks:")
        logger.info(f"    - Total Target URLs: {stats_after['total_target_urls']}")
        logger.info(f"    - Active URLs: {stats_after['active_urls']}")
        logger.info(f"    - Blocked URLs: {stats_after['blocked_urls']}")
        logger.info(f"  Domain Checks:")
        logger.info(f"    - Total Domain Checks: {dc_stats_after['total_domain_checks']}")
        logger.info(f"    - Active Domains: {dc_stats_after['active_domains']}")
        logger.info(f"    - Blocked Domains: {dc_stats_after['blocked_domains']}")
        
        # Summary
        end_time = datetime.now()
        duration = (end_time - start_time).total_seconds()
        
        logger.info("=" * 60)
        logger.info("Domain Checker Completed")
        logger.info(f"Duration: {duration:.2f} seconds")
        logger.info(f"Total Domains Checked: {len(all_domains_to_check)}")
        logger.info(f"  - Target URLs: {len(target_urls)}")
        logger.info(f"  - Domain Checks: {len(domain_checks)}")
        logger.info(f"Total Status Changes: {total_updates}")
        logger.info(f"  - Target URLs: {len(target_url_updates)}")
        logger.info(f"  - Domain Checks: {len(domain_check_updates)}")
        logger.info("=" * 60)
        
    except Exception as e:
        logger.error(f"Error in main process: {e}", exc_info=True)
        sys.exit(1)
    
    finally:
        # Cleanup
        await checker.close()
        await db.close()


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        logger.info("Process interrupted by user")
        sys.exit(0)

