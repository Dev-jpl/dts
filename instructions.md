# Basic usage
php artisan simulate:workflow

# With options
php artisan simulate:workflow --routing=single --action=fa --verbose-steps
php artisan simulate:workflow --routing=multiple --action=fi --close
php artisan simulate:workflow --routing=sequential --recipients=3 --action=fa
php artisan simulate:workflow --include-forward --verbose-steps
php artisan simulate:workflow --include-return --verbose-steps


simulate:workflow Options:
Option	Description
--routing=	single, multiple, sequential
--action=	random, fa, fi, or specific action name
--recipients=	Number of recipients (1-n)
--include-forward	Simulate forward action
--include-return	Simulate return to sender
--close	Close document after completion
--verbose-steps	Show detailed step output