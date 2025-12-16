"""
Telegram Bot untuk Domain Checker
Menggunakan python-telegram-bot untuk handle command dari user
"""
import asyncio
import logging
import sys
from telegram import Update
from telegram.ext import Application, CommandHandler, ContextTypes, MessageHandler, filters
from checker import DomainChecker
from config import LOG_LEVEL

# Setup logging
LOG_LEVELS = {
    'DEBUG': logging.DEBUG,
    'INFO': logging.INFO,
    'WARNING': logging.WARNING,
    'ERROR': logging.ERROR,
    'CRITICAL': logging.CRITICAL
}

log_level = LOG_LEVELS.get(LOG_LEVEL.upper() if isinstance(LOG_LEVEL, str) else 'INFO', logging.INFO)

logging.basicConfig(
    level=log_level,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('telegram_bot.log'),
        logging.StreamHandler(sys.stdout)
    ]
)

logger = logging.getLogger(__name__)

# Bot token
BOT_TOKEN = '8422912318:AAGxX8sld94TMHF1b_5M4FOyzYzDpmXB0ZE'

# Initialize domain checker
checker = DomainChecker()


async def start_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Handle /start command - kirim chat ID user"""
    try:
        chat_id = update.effective_chat.id
        user_name = update.effective_user.first_name or 'User'
        
        logger.info(f"Received /start command from chat_id: {chat_id}, user: {user_name}")
        
        message = f"Hello {user_name}! 👋\n\n"
        message += f"Your Telegram Chat ID is:\n"
        message += f"`{chat_id}`\n\n"
        message += "Use this Chat ID in your profile settings to receive domain status notifications.\n\n"
        message += "Available commands:\n"
        message += "/start - Get your Telegram Chat ID\n"
        message += "/check <domain> - Check domain status\n"
        message += "Example: /check google.com"
        
        await update.message.reply_text(message, parse_mode='Markdown')
        logger.info(f"✓ Sent /start response to chat_id: {chat_id}")
    except Exception as e:
        logger.error(f"Error in start_command: {e}", exc_info=True)
        try:
            await update.message.reply_text("❌ An error occurred. Please try again.")
        except:
            pass


async def check_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Handle /check command - cek status domain"""
    chat_id = update.effective_chat.id
    user_name = update.effective_user.first_name or 'User'
    
    # Cek apakah ada domain yang diberikan
    if not context.args or len(context.args) == 0:
        await update.message.reply_text(
            "❌ Please provide a domain to check.\n\n"
            "Usage: /check <domain>\n"
            "Example: /check google.com"
        )
        return
    
    # Ambil domain dari argument
    domain = context.args[0].strip()
    
    # Validasi domain (basic check)
    if not domain or len(domain) < 3:
        await update.message.reply_text("❌ Invalid domain format. Please provide a valid domain.")
        return
    
    # Hapus protocol jika ada
    domain = domain.replace('http://', '').replace('https://', '').strip()
    # Hapus trailing slash
    domain = domain.rstrip('/')
    # Ambil hanya domain (hapus path jika ada)
    domain = domain.split('/')[0].split('?')[0]
    
    try:
        # Kirim pesan "checking..."
        status_message = await update.message.reply_text(f"🔍 Checking domain: `{domain}`...", parse_mode='Markdown')
        
        # Initialize checker jika belum
        if checker.session is None:
            await checker.initialize()
        
        # Check domain via API
        results = await checker.check_domains_batch([domain])
        
        # Parse hasil
        if domain in results and isinstance(results[domain], dict):
            is_blocked = results[domain].get('blocked', False)
            status_icon = '🚫' if is_blocked else '✅'
            status_text = 'BLOCKED' if is_blocked else 'ACTIVE'
            
            response_message = f"{status_icon} *Domain Check Result*\n\n"
            response_message += f"*Domain:* `{domain}`\n"
            response_message += f"*Status:* {status_text}\n"
            
            if is_blocked:
                response_message += "\n⚠️ This domain is currently blocked."
            else:
                response_message += "\n✅ This domain is active and accessible."
            
            # Update pesan dengan hasil
            await status_message.edit_text(response_message, parse_mode='Markdown')
            logger.info(f"Checked domain {domain} for chat_id {chat_id}: {'blocked' if is_blocked else 'active'}")
        else:
            # Domain tidak ditemukan di hasil atau format tidak sesuai
            error_message = f"❌ *Error*\n\n"
            error_message += f"Unable to check domain: `{domain}`\n"
            error_message += "Please try again later or check if the domain is valid."
            await status_message.edit_text(error_message, parse_mode='Markdown')
            logger.warning(f"Domain {domain} not found in API results for chat_id {chat_id}")
            
    except Exception as e:
        error_message = f"❌ *Error*\n\n"
        error_message += f"An error occurred while checking domain: `{domain}`\n"
        error_message += "Please try again later."
        await update.message.reply_text(error_message, parse_mode='Markdown')
        logger.error(f"Error checking domain {domain} for chat_id {chat_id}: {e}", exc_info=True)


async def handle_message(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Handle all text messages (for debugging)"""
    try:
        if update.message and update.message.text:
            text = update.message.text
            chat_id = update.effective_chat.id
            logger.info(f"Received message from chat_id {chat_id}: {text}")
            
            # Jika bukan command, beri tahu user tentang command yang tersedia
            if not text.startswith('/'):
                await update.message.reply_text(
                    "Hi! 👋\n\n"
                    "Available commands:\n"
                    "/start - Get your Telegram Chat ID\n"
                    "/check <domain> - Check domain status\n"
                    "Example: /check google.com"
                )
    except Exception as e:
        logger.error(f"Error in handle_message: {e}", exc_info=True)


async def error_handler(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Handle errors"""
    logger.error(f"Update {update} caused error {context.error}", exc_info=context.error)
    
    if update and update.effective_message:
        try:
            await update.effective_message.reply_text(
                "❌ An error occurred. Please try again later."
            )
        except Exception as e:
            logger.error(f"Error sending error message: {e}")


async def post_init(application: Application) -> None:
    """Initialize domain checker after bot is ready"""
    await checker.initialize()
    logger.info("Domain checker initialized for Telegram bot")


async def post_shutdown(application: Application) -> None:
    """Cleanup when bot is shutting down"""
    await checker.close()
    logger.info("Domain checker closed")


def main():
    """Main function to run the bot"""
    logger.info("=" * 60)
    logger.info("Starting Telegram Bot")
    logger.info("=" * 60)
    
    try:
        # Create application
        logger.info("Creating application...")
        application = Application.builder().token(BOT_TOKEN).build()
        logger.info("Application created successfully")
        
        # Add command handlers
        logger.info("Adding command handlers...")
        application.add_handler(CommandHandler("start", start_command))
        application.add_handler(CommandHandler("check", check_command))
        logger.info("Command handlers added")
        
        # Add message handler for all text messages (for debugging)
        application.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, handle_message))
        logger.info("Message handler added")
        
        # Add error handler
        application.add_error_handler(error_handler)
        logger.info("Error handler added")
        
        # Add post_init and post_shutdown
        application.post_init = post_init
        application.post_shutdown = post_shutdown
        
        # Run bot
        logger.info("Bot is running... Press Ctrl+C to stop")
        logger.info("Waiting for messages...")
        logger.info("To test: Send /start to your bot in Telegram")
        application.run_polling(allowed_updates=Update.ALL_TYPES, drop_pending_updates=True)
    except KeyboardInterrupt:
        logger.info("Bot stopped by user")
    except Exception as e:
        logger.error(f"Bot error: {e}", exc_info=True)
        print(f"\n❌ Error: {e}")
        print("Please check:")
        print("1. Bot token is correct")
        print("2. Bot is started in BotFather")
        print("3. Internet connection is available")
    finally:
        logger.info("Bot shutdown complete")


if __name__ == "__main__":
    main()

