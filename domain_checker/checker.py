"""
Domain checking logic with async batch processing
"""
import aiohttp
import asyncio
import logging
from typing import List, Dict, Optional
from urllib.parse import urlencode
from config import API_URL, BATCH_SIZE, MAX_CONCURRENT_REQUESTS, REQUEST_TIMEOUT

logger = logging.getLogger(__name__)


class DomainChecker:
    """Domain checker with batch processing"""
    
    def __init__(self):
        self.session: Optional[aiohttp.ClientSession] = None
    
    async def initialize(self):
        """Initialize HTTP session"""
        timeout = aiohttp.ClientTimeout(total=REQUEST_TIMEOUT)
        self.session = aiohttp.ClientSession(timeout=timeout)
        logger.info("HTTP session initialized")
    
    async def close(self):
        """Close HTTP session"""
        if self.session:
            await self.session.close()
            logger.info("HTTP session closed")
    
    async def check_domains_batch(self, domains: List[str]) -> Dict[str, Dict]:
        """
        Check multiple domains in a single API request
        Returns: Dict with domain as key and {'blocked': bool} as value
        """
        if not domains:
            return {}
        
        # Prepare query string
        domains_str = ','.join(domains)
        url = f"{API_URL}?domains={domains_str}"
        
        try:
            async with self.session.get(url) as response:
                if response.status == 200:
                    data = await response.json()
                    logger.debug(f"Checked {len(domains)} domains, got {len(data)} results")
                    return data
                else:
                    logger.error(f"API returned status {response.status} for {len(domains)} domains")
                    return {}
        except asyncio.TimeoutError:
            logger.error(f"Timeout checking {len(domains)} domains")
            return {}
        except Exception as e:
            logger.error(f"Error checking domains: {e}")
            return {}
    
    def split_into_batches(self, items: List, batch_size: int) -> List[List]:
        """Split list into batches"""
        return [items[i:i + batch_size] for i in range(0, len(items), batch_size)]
    
    async def check_all_domains(self, items: List[Dict]) -> Dict[str, bool]:
        """
        Check all domains with parallel batch processing
        Args:
            items: List of dicts with 'id' and 'domain' keys
        Returns: Dict with item id as key and is_blocked (bool) as value
        """
        if not items:
            return {}
        
        # Group by domain to avoid duplicate checks
        domain_map = {}
        id_to_domain = {}
        for item in items:
            domain = item.get('domain')
            item_id = item.get('id')
            if domain and item_id:
                id_to_domain[item_id] = domain
                if domain not in domain_map:
                    domain_map[domain] = []
                domain_map[domain].append(item_id)
        
        unique_domains = list(domain_map.keys())
        logger.info(f"Checking {len(unique_domains)} unique domains from {len(items)} items")
        
        # Split into batches
        domain_batches = self.split_into_batches(unique_domains, BATCH_SIZE)
        logger.info(f"Split into {len(domain_batches)} batches of max {BATCH_SIZE} domains each")
        
        # Create semaphore to limit concurrent requests
        semaphore = asyncio.Semaphore(MAX_CONCURRENT_REQUESTS)
        
        async def check_batch_with_semaphore(batch: List[str]) -> Dict[str, Dict]:
            async with semaphore:
                return await self.check_domains_batch(batch)
        
        # Process all batches concurrently
        tasks = [check_batch_with_semaphore(batch) for batch in domain_batches]
        results = await asyncio.gather(*tasks, return_exceptions=True)
        
        # Combine all results
        all_results = {}
        for result in results:
            if isinstance(result, Exception):
                logger.error(f"Error in batch: {result}")
                continue
            all_results.update(result)
        
        # Convert to simple blocked status dict by domain
        domain_blocked_status = {}
        for domain, data in all_results.items():
            if isinstance(data, dict) and 'blocked' in data:
                domain_blocked_status[domain] = data['blocked']
            else:
                # Default to not blocked if response format is unexpected
                domain_blocked_status[domain] = False
                logger.warning(f"Unexpected response format for domain {domain}: {data}")
        
        # Map back to all item IDs
        final_results = {}
        for item_id, domain in id_to_domain.items():
            if domain in domain_blocked_status:
                final_results[item_id] = domain_blocked_status[domain]
            else:
                # If domain not found in results, default to False (not blocked)
                final_results[item_id] = False
                logger.warning(f"Domain {domain} (ID: {item_id}) not found in API results")
        
        logger.info(f"Completed checking {len(domain_blocked_status)} unique domains")
        return final_results

