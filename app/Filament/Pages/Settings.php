<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Models\Setting;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.pages.settings';

    // Define properties for the form
    public ?array $data = [];

    public function mount(): void
    {
        // Load settings from DB into the form data
        $this->form->fill([
            // General & Branding
            'site_name' => Setting::get('site_name', 'National Resource Benefits'),
            'site_logo' => Setting::get('site_logo'),
            'site_logo_dark' => Setting::get('site_logo_dark'),
            'site_favicon' => Setting::get('site_favicon'),
            'support_email' => Setting::get('support_email'),
            'support_phone' => Setting::get('support_phone'),
            'address' => Setting::get('address'),
            'social_facebook' => Setting::get('social_facebook'),
            'social_twitter' => Setting::get('social_twitter'),
            'social_instagram' => Setting::get('social_instagram'),
            'social_linkedin' => Setting::get('social_linkedin'),
            'social_youtube' => Setting::get('social_youtube'),
            'social_tiktok' => Setting::get('social_tiktok'),
            
            // SEO Settings
            'seo_meta_title' => Setting::get('seo_meta_title'),
            'seo_meta_description' => Setting::get('seo_meta_description'),
            'seo_meta_keywords' => Setting::get('seo_meta_keywords'),
            'seo_og_image' => Setting::get('seo_og_image'),
            'seo_google_analytics' => Setting::get('seo_google_analytics'),
            'seo_google_tag_manager' => Setting::get('seo_google_tag_manager'),
            'seo_facebook_pixel' => Setting::get('seo_facebook_pixel'),
            'seo_robots_txt' => Setting::get('seo_robots_txt', "User-agent: *\nAllow: /"),
            
            // Mail / SMTP
            'mail_mailer' => Setting::get('mail_mailer', 'smtp'),
            'mail_host' => Setting::get('mail_host', ''),
            'mail_port' => Setting::get('mail_port', '2525'),
            'mail_username' => Setting::get('mail_username'),
            'mail_password' => Setting::get('mail_password'),
            'mail_encryption' => Setting::get('mail_encryption', 'tls'),
            'mail_from_address' => Setting::get('mail_from_address'),
            'mail_from_name' => Setting::get('mail_from_name'),

            // Funding & Finance
            'hero_title' => Setting::get('hero_title', 'Applications are NOW Available!'),
            'applications_active' => Setting::get('applications_active', true),
            'registration_fee' => Setting::get('registration_fee', 0),
            
            // Limits & Fees (Moved)
            'deposit_min' => Setting::get('deposit_min', 10),
            'deposit_max' => Setting::get('deposit_max', 50000),
            'deposit_limit_daily' => Setting::get('deposit_limit_daily', 100000),
            
            'withdrawal_min' => Setting::get('withdrawal_min', 50),
            'withdrawal_max' => Setting::get('withdrawal_max', 50000),
            'withdrawal_limit_daily' => Setting::get('withdrawal_limit_daily', 25000),
            
            // Internal Transfers
            'transfer_internal_active' => Setting::get('transfer_internal_active', true),
            'transfer_self_active' => Setting::get('transfer_self_active', true),
            'transfer_internal_min' => Setting::get('transfer_internal_min', 1),
            'transfer_internal_max' => Setting::get('transfer_internal_max', 5000),
            'transfer_internal_fee_percent' => Setting::get('transfer_internal_fee_percent', 0),
            'transfer_internal_fee_fixed' => Setting::get('transfer_internal_fee_fixed', 0),

            // Wire / Domestic Caps
            'transfer_wire_max' => Setting::get('transfer_wire_max', 100000),
            'transfer_domestic_max' => Setting::get('transfer_domestic_max', 50000),

            // Referral
            'referral_enabled' => Setting::get('referral_enabled', true),
            'referral_bonus' => Setting::get('referral_bonus', 10),

            // Loan Settings
            'loans_enabled' => Setting::get('loans_enabled', true),
            'loan_min_amount' => Setting::get('loan_min_amount', 100),
            'loan_max_amount' => Setting::get('loan_max_amount', 10000),
            'loan_interest_rate' => Setting::get('loan_interest_rate', 5),
            
            // Payment Gateway Settings
            'payment_gateway_mode' => Setting::get('payment_gateway_mode', 'both'), // auto, manual, both
            'payment_auto_gateways_enabled' => Setting::get('payment_auto_gateways_enabled', true),
            'payment_manual_gateways_enabled' => Setting::get('payment_manual_gateways_enabled', true),
            'payment_default_gateway' => Setting::get('payment_default_gateway'),
            
            // Feature Toggles (Platform Features)
            'feature_deposit' => Setting::get('feature_deposit', true),
            'feature_withdraw' => Setting::get('feature_withdraw', true),
            'feature_withdraw_bank' => Setting::get('feature_withdraw_bank', true),
            'feature_withdraw_express' => Setting::get('feature_withdraw_express', true),
            'feature_withdraw_verification' => Setting::get('feature_withdraw_verification', true),
            'feature_transfer' => Setting::get('feature_transfer', true),
            'feature_transfer_internal' => Setting::get('feature_transfer_internal', true),
            'feature_transfer_own' => Setting::get('feature_transfer_own', true),
            'feature_transfer_domestic' => Setting::get('feature_transfer_domestic', true),
            'feature_transfer_wire' => Setting::get('feature_transfer_wire', true),
            'feature_voucher' => Setting::get('feature_voucher', true),
            'feature_loans' => Setting::get('feature_loans', true),
            'feature_grants' => Setting::get('feature_grants', true),
            'feature_funding_sources' => Setting::get('feature_funding_sources', true),
            'feature_applications' => Setting::get('feature_applications', true),
            'feature_kyc' => Setting::get('feature_kyc', true),
            'feature_support' => Setting::get('feature_support', true),
            'feature_ranks' => Setting::get('feature_ranks', true),
            'feature_referrals' => Setting::get('feature_referrals', true),
            'feature_notifications' => Setting::get('feature_notifications', true),
            'feature_transactions' => Setting::get('feature_transactions', true),
            'feature_accounts' => Setting::get('feature_accounts', true),
            
            // Payment
            'currency_symbol' => Setting::get('currency_symbol', '$'),
            'maintenance_mode' => Setting::get('maintenance_mode', false),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        // GENERAL SETTINGS TAB (FIRST)
                        Forms\Components\Tabs\Tab::make('General')
                            ->icon('heroicon-m-globe-alt')
                            ->schema([
                                Forms\Components\Section::make('Site Identity')
                                    ->description('Configure your site name.')
                                    ->schema([
                                        Forms\Components\TextInput::make('site_name')
                                            ->label('Site Name')
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Contact Information')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('support_email')
                                                ->email()
                                                ->label('Support Email'),
                                            Forms\Components\TextInput::make('support_phone')
                                                ->tel()
                                                ->label('Support Phone'),
                                        ]),
                                        Forms\Components\Textarea::make('address')
                                            ->rows(2),
                                    ]),
                                    
                                Forms\Components\Section::make('Social Media Links')
                                    ->description('Add your social media profile URLs.')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('social_facebook')->label('Facebook URL')->prefixIcon('heroicon-o-chat-bubble-oval-left'),
                                            Forms\Components\TextInput::make('social_twitter')->label('Twitter/X URL')->prefixIcon('heroicon-o-chat-bubble-bottom-center-text'),
                                            Forms\Components\TextInput::make('social_instagram')->label('Instagram URL')->prefixIcon('heroicon-o-camera'),
                                            Forms\Components\TextInput::make('social_linkedin')->label('LinkedIn URL')->prefixIcon('heroicon-o-briefcase'),
                                            Forms\Components\TextInput::make('social_youtube')->label('YouTube URL')->prefixIcon('heroicon-o-play-circle'),
                                            Forms\Components\TextInput::make('social_tiktok')->label('TikTok URL')->prefixIcon('heroicon-o-musical-note'),
                                        ]),
                                    ]),
                            ]),

                        // BRANDING TAB (SECOND)
                        Forms\Components\Tabs\Tab::make('Branding')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                Forms\Components\Section::make('Logo & Favicon')
                                    ->description('Upload logos for light and dark modes, and your site favicon.')
                                    ->schema([
                                        Forms\Components\FileUpload::make('site_logo')
                                            ->label('Logo (Light Mode)')
                                            ->helperText('Used on light backgrounds. Recommended: PNG with transparent background.')
                                            ->image()
                                            ->imagePreviewHeight('80')
                                            ->directory('settings')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/jpeg', 'image/webp'])
                                            ->maxSize(2048),
                                            
                                        Forms\Components\FileUpload::make('site_logo_dark')
                                            ->label('Logo (Dark Mode)')
                                            ->helperText('Used on dark backgrounds. If not set, light logo will be used.')
                                            ->image()
                                            ->imagePreviewHeight('80')
                                            ->directory('settings')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/jpeg', 'image/webp'])
                                            ->maxSize(2048),
                                            
                                        Forms\Components\FileUpload::make('site_favicon')
                                            ->label('Favicon')
                                            ->helperText('Recommended: 32x32px or 64x64px PNG/ICO file.')
                                            ->image()
                                            ->imagePreviewHeight('48')
                                            ->directory('settings')
                                            ->visibility('public')
                                            ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/ico', 'image/svg+xml', 'image/vnd.microsoft.icon'])
                                            ->maxSize(512),
                                    ])->columns(3),
                            ]),

                        // SEO TAB
                        Forms\Components\Tabs\Tab::make('SEO')
                            ->icon('heroicon-m-magnifying-glass')
                            ->schema([
                                Forms\Components\Section::make('Meta Tags')
                                    ->description('Configure default meta tags for search engines.')
                                    ->schema([
                                        Forms\Components\TextInput::make('seo_meta_title')
                                            ->label('Default Meta Title')
                                            ->helperText('Appears in browser tabs and search results. Max 60 characters recommended.')
                                            ->maxLength(70)
                                            ->columnSpanFull(),
                                            
                                        Forms\Components\Textarea::make('seo_meta_description')
                                            ->label('Default Meta Description')
                                            ->helperText('Appears in search results. Max 160 characters recommended.')
                                            ->maxLength(200)
                                            ->rows(3)
                                            ->columnSpanFull(),
                                            
                                        Forms\Components\TagsInput::make('seo_meta_keywords')
                                            ->label('Meta Keywords')
                                            ->helperText('Add relevant keywords (press Enter after each).')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Open Graph / Social Sharing')
                                    ->description('Image shown when your site is shared on social media.')
                                    ->schema([
                                        Forms\Components\FileUpload::make('seo_og_image')
                                            ->label('OG Image')
                                            ->helperText('Recommended: 1200x630px for best display on social platforms.')
                                            ->image()
                                            ->imagePreviewHeight('150')
                                            ->directory('settings')
                                            ->visibility('public')
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                                            ->maxSize(2048),
                                    ]),

                                Forms\Components\Section::make('Analytics & Tracking')
                                    ->description('Add tracking codes for analytics services.')
                                    ->schema([
                                        Forms\Components\TextInput::make('seo_google_analytics')
                                            ->label('Google Analytics ID')
                                            ->placeholder('G-XXXXXXXXXX or UA-XXXXXXXX-X')
                                            ->helperText('Your Google Analytics measurement ID.'),
                                            
                                        Forms\Components\TextInput::make('seo_google_tag_manager')
                                            ->label('Google Tag Manager ID')
                                            ->placeholder('GTM-XXXXXXX')
                                            ->helperText('Your GTM container ID.'),
                                            
                                        Forms\Components\TextInput::make('seo_facebook_pixel')
                                            ->label('Facebook Pixel ID')
                                            ->placeholder('XXXXXXXXXXXXXXXX')
                                            ->helperText('Your Facebook Pixel ID for tracking.'),
                                    ])->columns(3),

                                Forms\Components\Section::make('Robots.txt Content')
                                    ->description('Control how search engines crawl your site.')
                                    ->schema([
                                        Forms\Components\Textarea::make('seo_robots_txt')
                                            ->label('Robots.txt')
                                            ->helperText('This will be served at /robots.txt')
                                            ->rows(6)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                            
                        // MAIL / SMTP TAB
                        Forms\Components\Tabs\Tab::make('Mail Configuration')
                            ->icon('heroicon-m-envelope')
                            ->schema([
                                Forms\Components\Section::make('SMTP Settings')
                                    ->description('Configure your email sending provider.')
                                    ->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\Select::make('mail_mailer')
                                                ->label('Mailer')
                                                ->options([
                                                    'smtp' => 'SMTP',
                                                    'sendmail' => 'Sendmail',
                                                    'log' => 'Log (Dev)',
                                                ])
                                                ->default('smtp')
                                                ->required(),
                                            Forms\Components\TextInput::make('mail_host')
                                                ->label('Host')
                                                ->placeholder('smtp.example.com'),
                                            Forms\Components\TextInput::make('mail_port')
                                                ->label('Port')
                                                ->numeric()
                                                ->default(587),
                                        ]),
                                        
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('mail_username')
                                                ->label('Username'),
                                            Forms\Components\TextInput::make('mail_password')
                                                ->password()
                                                ->revealable()
                                                ->label('Password'),
                                        ]),
                                        
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\Select::make('mail_encryption')
                                                ->label('Encryption')
                                                ->options([
                                                    'tls' => 'TLS',
                                                    'ssl' => 'SSL',
                                                    'null' => 'None',
                                                ])
                                                ->default('tls'),
                                            Forms\Components\TextInput::make('mail_from_address')
                                                ->label('From Address')
                                                ->email()
                                                ->placeholder('noreply@example.com'),
                                            Forms\Components\TextInput::make('mail_from_name')
                                                ->label('From Name')
                                                ->placeholder('System Notification'),
                                        ]),
                                    ]),
                            ]),

                        // FUNDING SETTINGS TAB
                        Forms\Components\Tabs\Tab::make('Finance & Funding')
                            ->icon('heroicon-m-banknotes')
                            ->schema([
                                Forms\Components\Section::make('Funding Logic')
                                    ->description('Configure how applications are handled.')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('applications_active')
                                                ->label('Accepting Applications')
                                                ->helperText('Turn off to stop new registrations/applications.'),
                                                
                                            Forms\Components\TextInput::make('registration_fee')
                                                ->label('Research Service Fee')
                                                ->numeric()
                                                ->prefix('$')
                                                ->helperText('Amount to charge for "Research Service" access. Set 0 for free.'),
                                        ]),
                                            
                                        Forms\Components\TextInput::make('hero_title')
                                            ->label('Homepage Hero Title')
                                            ->helperText('The main headline on the welcome page.')
                                            ->columnSpanFull(),
                                    ])->columns(2),
                            ]),

                        // LIMITS & FEES TAB
                        Forms\Components\Tabs\Tab::make('Limits & Fees')
                            ->icon('heroicon-m-scale')
                            ->schema([
                                Forms\Components\Section::make('Global Platform Limits')
                                    ->description('These limits act as hard caps for all transaction types. Individual Gateways may have lower limits.')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Group::make([
                                                Forms\Components\TextInput::make('deposit_min')
                                                    ->label('Min Deposit')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->default(10),
                                                Forms\Components\TextInput::make('deposit_max')
                                                    ->label('Max Deposit (Per Trx)')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->default(50000),
                                                Forms\Components\TextInput::make('deposit_limit_daily')
                                                    ->label('Max Deposit (Daily)')
                                                    ->numeric()
                                                    ->prefix('$'),
                                            ])->label('Deposit Controls'),

                                            Forms\Components\Group::make([
                                                Forms\Components\TextInput::make('withdrawal_min')
                                                    ->label('Min Withdrawal')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->default(50),
                                                Forms\Components\TextInput::make('withdrawal_max')
                                                    ->label('Max Withdrawal (Per Trx)')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->default(50000),
                                                Forms\Components\TextInput::make('withdrawal_limit_daily')
                                                    ->label('Max Withdrawal (Daily)')
                                                    ->numeric()
                                                    ->prefix('$'),
                                            ])->label('Withdrawal Controls'),
                                        ]),
                                    ]),

                                Forms\Components\Section::make('Internal Transfer Settings')
                                    ->description('Controls for User-to-User transfers within the platform.')
                                    ->schema([
                                        Forms\Components\Toggle::make('transfer_internal_active')
                                            ->label('Enable Internal Transfers (User-to-User)')
                                            ->default(true),
                                            
                                        Forms\Components\Toggle::make('transfer_self_active')
                                            ->label('Enable Self Transfers (Account-to-Account)')
                                            ->helperText('Allow users to move funds between their own wallets.')
                                            ->default(true),

                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('transfer_internal_min')
                                                ->label('Min Transfer')
                                                ->numeric()
                                                ->prefix('$'),
                                            Forms\Components\TextInput::make('transfer_internal_max')
                                                ->label('Max Transfer')
                                                ->numeric()
                                                ->prefix('$'),
                                            Forms\Components\TextInput::make('transfer_internal_fee_percent')
                                                ->label('Fee Percentage')
                                                ->numeric()
                                                ->suffix('%')
                                                ->step(0.01),
                                            Forms\Components\TextInput::make('transfer_internal_fee_fixed')
                                                ->label('Fixed Fee')
                                                ->numeric()
                                                ->prefix('$')
                                                ->step(0.01),
                                        ]),
                                    ]),

                                Forms\Components\Section::make('External Transfer Caps')
                                    ->description('Global caps for Wire and Domestic transfers. Fees for these are configured in Payment Gateways.')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('transfer_wire_max')
                                                ->label('Max Wire Transfer')
                                                ->numeric()
                                                ->prefix('$'),
                                            Forms\Components\TextInput::make('transfer_domestic_max')
                                                ->label('Max Domestic Transfer')
                                                ->numeric()
                                                ->prefix('$'),
                                        ]),
                                    ]),
                            ]),

                            
                        // REFERRAL SETTINGS TAB
                        Forms\Components\Tabs\Tab::make('Referrals & Ranks')
                            ->icon('heroicon-m-user-group')
                            ->schema([
                                Forms\Components\Section::make('Referral Program')
                                    ->schema([
                                        Forms\Components\Toggle::make('referral_enabled')
                                            ->label('Enable Referral Program'),
                                            
                                        Forms\Components\TextInput::make('referral_bonus')
                                            ->label('Direct Referral Bonus')
                                            ->numeric()
                                            ->prefix('$')
                                            ->helperText('Bonus credited to referrer when a new user joins/verifies.'),
                                    ]),

                                Forms\Components\Section::make('Rank System')
                                    ->description('Configure how user ranks are calculated.')
                                    ->schema([
                                        Forms\Components\Toggle::make('auto_rank_upgrade')
                                            ->label('Automatic Rank Upgrades')
                                            ->helperText('If enabled, the system will automatically promote users based on volume.'),
                                            
                                        Forms\Components\Placeholder::make('rank_info')
                                            ->label('Rank Configuration')
                                            ->content('Rank thresholds and commission rates are managed in the dedicated database tables. Use this space for global toggles.'),
                                    ]),

                                Forms\Components\Section::make('Loan Configuration')
                                    ->description('Configure global loan parameters.')
                                    ->schema([
                                        Forms\Components\Toggle::make('loans_enabled')
                                            ->label('Enable Loan Requests')
                                            ->helperText('Allow active users to request loans.')
                                            ->default(true),
                                            
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('loan_min_amount')
                                                ->label('Minimum Amount')
                                                ->numeric()
                                                ->prefix('$')
                                                ->default(100),
                                            Forms\Components\TextInput::make('loan_max_amount')
                                                ->label('Maximum Amount')
                                                ->numeric()
                                                ->prefix('$')
                                                ->default(10000),
                                            Forms\Components\TextInput::make('loan_interest_rate')
                                                ->label('Default Interest Rate')
                                                ->numeric()
                                                ->suffix('%')
                                                ->default(5),
                                        ]),
                                    ]),
                            ]),
                            
                        // PAYMENT GATEWAYS TAB
                        Forms\Components\Tabs\Tab::make('Payment Gateways')
                            ->icon('heroicon-m-credit-card')
                            ->schema([
                                Forms\Components\Section::make('Gateway Mode')
                                    ->description('Configure how payment gateways are used across the platform')
                                    ->schema([
                                        Forms\Components\Select::make('payment_gateway_mode')
                                            ->label('Gateway Mode')
                                            ->options([
                                                'auto' => 'Automatic Only (API-based)',
                                                'manual' => 'Manual Only (Bank Transfer, Crypto)',
                                                'both' => 'Both Automatic & Manual',
                                            ])
                                            ->default('both')
                                            ->helperText('Choose which types of payment gateways users can use'),
                                            
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('payment_auto_gateways_enabled')
                                                ->label('Enable Automatic Gateways')
                                                ->helperText('Stripe, Paystack, Flutterwave')
                                                ->default(true),
                                                
                                            Forms\Components\Toggle::make('payment_manual_gateways_enabled')
                                                ->label('Enable Manual Gateways')
                                                ->helperText('Bank Transfer, Crypto')
                                                ->default(true),
                                        ]),
                                    ]),

                                Forms\Components\Section::make('Manage Gateways')
                                    ->description('Add and configure payment gateways')
                                    ->schema([
                                        Forms\Components\Placeholder::make('gateway_link')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div class="flex items-center gap-4">
                                                    <a href="' . route('filament.admin.resources.payment-gateways.index') . '" 
                                                       class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm outline-none transition-all hover:bg-primary-500 focus:ring-2 focus:ring-primary-500/50">
                                                        <span>Manage Payment Gateways</span>
                                                    </a>
                                                    <span class="text-sm text-gray-500">Configure Stripe, Paystack, Flutterwave, Bank Transfer, Crypto, and more.</span>
                                                </div>
                                            ')),
                                    ]),

                                Forms\Components\Section::make('Available Gateways')
                                    ->description('Quick overview of configured gateways')
                                    ->schema([
                                        Forms\Components\Placeholder::make('auto_gateways')
                                            ->label('Automatic Gateways')
                                            ->content(function () {
                                                $gateways = \App\Models\PaymentGateway::automatic()->active()->get();
                                                if ($gateways->isEmpty()) {
                                                    return 'No automatic gateways configured';
                                                }
                                                return $gateways->pluck('name')->implode(', ');
                                            }),
                                            
                                        Forms\Components\Placeholder::make('manual_gateways')
                                            ->label('Manual Gateways')
                                            ->content(function () {
                                                $gateways = \App\Models\PaymentGateway::manual()->active()->get();
                                                if ($gateways->isEmpty()) {
                                                    return 'No manual gateways configured';
                                                }
                                                return $gateways->pluck('name')->implode(', ');
                                            }),
                                    ])->columns(2),
                            ]),
                            
                        // FEATURES TAB - Control platform features visibility
                        Forms\Components\Tabs\Tab::make('Features')
                            ->icon('heroicon-m-squares-plus')
                            ->schema([
                                Forms\Components\Section::make('Platform Features')
                                    ->description('Enable or disable features across the platform. Disabled features will be hidden from user sidebar and access will be blocked.')
                                    ->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\Toggle::make('feature_accounts')
                                                ->label('Accounts')
                                                ->helperText('View account balances')
                                                ->default(true),
                                                
                                            Forms\Components\Toggle::make('feature_transactions')
                                                ->label('Transactions')
                                                ->helperText('Transaction history')
                                                ->default(true),
                                                
                                            Forms\Components\Toggle::make('feature_deposit')
                                                ->label('Deposits')
                                                ->helperText('Fund account')
                                                ->default(true),
                                        ]),
                                    ]),

                                Forms\Components\Section::make('Transfer Features')
                                    ->description('Control different types of transfers')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('feature_transfer')
                                                ->label('Transfers (Master)')
                                                ->helperText('Master toggle for all transfers')
                                                ->default(true)
                                                ->live(),
                                        ]),
                                        
                                        Forms\Components\Grid::make(4)->schema([
                                            Forms\Components\Toggle::make('feature_transfer_internal')
                                                ->label('Send to User')
                                                ->helperText('Transfer to other users')
                                                ->default(true)
                                                ->disabled(fn ($get) => !$get('feature_transfer')),
                                                
                                            Forms\Components\Toggle::make('feature_transfer_own')
                                                ->label('Between Accounts')
                                                ->helperText('Transfer between own wallets')
                                                ->default(true)
                                                ->disabled(fn ($get) => !$get('feature_transfer')),
                                                
                                            Forms\Components\Toggle::make('feature_transfer_domestic')
                                                ->label('Domestic Transfer')
                                                ->helperText('Local bank transfers')
                                                ->default(true)
                                                ->disabled(fn ($get) => !$get('feature_transfer')),
                                                
                                            Forms\Components\Toggle::make('feature_transfer_wire')
                                                ->label('Wire Transfer')
                                                ->helperText('International wire transfers')
                                                ->default(true)
                                                ->disabled(fn ($get) => !$get('feature_transfer')),
                                        ]),
                                    ]),

                                Forms\Components\Section::make('Withdrawal Features')
                                    ->description('Control withdrawal-related features')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('feature_withdraw')
                                                ->label('Withdrawals (Master)')
                                                ->helperText('Master toggle for all withdrawals')
                                                ->default(true)
                                                ->live(),
                                                
                                            Forms\Components\Toggle::make('feature_voucher')
                                                ->label('Vouchers')
                                                ->helperText('Voucher redemption system')
                                                ->default(true),
                                        ]),
                                        
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\Toggle::make('feature_withdraw_bank')
                                                ->label('Bank Withdrawal')
                                                ->helperText('Manual bank withdrawals')
                                                ->default(true)
                                                ->disabled(fn ($get) => !$get('feature_withdraw')),
                                                
                                            Forms\Components\Toggle::make('feature_withdraw_express')
                                                ->label('Express Withdrawal')
                                                ->helperText('Automatic/instant withdrawals')
                                                ->default(true)
                                                ->disabled(fn ($get) => !$get('feature_withdraw')),
                                                
                                            Forms\Components\Toggle::make('feature_withdraw_verification')
                                                ->label('Withdrawal Verification')
                                                ->helperText('Verification codes for withdrawal')
                                                ->default(true)
                                                ->disabled(fn ($get) => !$get('feature_withdraw')),
                                        ]),
                                    ]),

                                Forms\Components\Section::make('Financial Services')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('feature_loans')
                                                ->label('Loans')
                                                ->helperText('Loan request system')
                                                ->default(true),
                                                
                                            Forms\Components\Toggle::make('feature_grants')
                                                ->label('Grants')
                                                ->helperText('Grant programs')
                                                ->default(true),
                                                
                                            Forms\Components\Toggle::make('feature_funding_sources')
                                                ->label('Funding Sources')
                                                ->helperText('Browse funding opportunities')
                                                ->default(true),
                                                
                                            Forms\Components\Toggle::make('feature_applications')
                                                ->label('My Applications')
                                                ->helperText('Application tracking')
                                                ->default(true),
                                        ]),
                                    ]),

                                Forms\Components\Section::make('Support & User Features')
                                    ->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\Toggle::make('feature_kyc')
                                                ->label('Identity Verification')
                                                ->helperText('KYC/ID verification')
                                                ->default(true),
                                                
                                            Forms\Components\Toggle::make('feature_support')
                                                ->label('Support Tickets')
                                                ->helperText('Help desk system')
                                                ->default(true),
                                                
                                            Forms\Components\Toggle::make('feature_notifications')
                                                ->label('Notifications')
                                                ->helperText('User notifications')
                                                ->default(true),
                                        ]),
                                    ]),

                                Forms\Components\Section::make('Engagement Features')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('feature_ranks')
                                                ->label('Ranks')
                                                ->helperText('User ranking system')
                                                ->default(true),
                                                
                                            Forms\Components\Toggle::make('feature_referrals')
                                                ->label('Referrals')
                                                ->helperText('Referral program')
                                                ->default(true),
                                        ]),
                                    ]),
                            ]),
                            
                        // SYSTEM SETTINGS TAB
                        Forms\Components\Tabs\Tab::make('System')
                            ->icon('heroicon-m-server')
                            ->schema([
                                Forms\Components\Section::make('Advanced Config')
                                    ->schema([
                                        Forms\Components\TextInput::make('currency_symbol')
                                            ->label('Currency Symbol')
                                            ->default('$')
                                            ->maxLength(5),
                                            
                                        Forms\Components\Toggle::make('maintenance_mode')
                                            ->label('Maintenance Mode')
                                            ->helperText('Put the site into maintenance mode (users cannot login).')
                                            ->onColor('danger'),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        // Logo & Branding
        Setting::set('site_name', $state['site_name'], 'general');
        Setting::set('site_logo', $state['site_logo'], 'general');
        Setting::set('site_logo_dark', $state['site_logo_dark'], 'general');
        Setting::set('site_favicon', $state['site_favicon'], 'general');
        
        // General / Contact
        Setting::set('support_email', $state['support_email'], 'general');
        Setting::set('support_phone', $state['support_phone'], 'general');
        Setting::set('address', $state['address'], 'general');
        Setting::set('social_facebook', $state['social_facebook'], 'general');
        Setting::set('social_twitter', $state['social_twitter'], 'general');
        Setting::set('social_instagram', $state['social_instagram'], 'general');
        Setting::set('social_linkedin', $state['social_linkedin'], 'general');
        Setting::set('social_youtube', $state['social_youtube'], 'general');
        Setting::set('social_tiktok', $state['social_tiktok'], 'general');

        // SEO
        Setting::set('seo_meta_title', $state['seo_meta_title'], 'seo');
        Setting::set('seo_meta_description', $state['seo_meta_description'], 'seo');
        Setting::set('seo_meta_keywords', $state['seo_meta_keywords'], 'seo', 'json');
        Setting::set('seo_og_image', $state['seo_og_image'], 'seo');
        Setting::set('seo_google_analytics', $state['seo_google_analytics'], 'seo');
        Setting::set('seo_google_tag_manager', $state['seo_google_tag_manager'], 'seo');
        Setting::set('seo_facebook_pixel', $state['seo_facebook_pixel'], 'seo');
        Setting::set('seo_robots_txt', $state['seo_robots_txt'], 'seo');

        // Mail
        Setting::set('mail_mailer', $state['mail_mailer'], 'mail');
        Setting::set('mail_host', $state['mail_host'], 'mail');
        Setting::set('mail_port', $state['mail_port'], 'mail');
        Setting::set('mail_username', $state['mail_username'], 'mail');
        Setting::set('mail_password', $state['mail_password'], 'mail');
        Setting::set('mail_encryption', $state['mail_encryption'], 'mail');
        Setting::set('mail_from_address', $state['mail_from_address'], 'mail');
        Setting::set('mail_from_name', $state['mail_from_name'], 'mail');
        
        // Funding
        Setting::set('hero_title', $state['hero_title'], 'funding');
        Setting::set('applications_active', $state['applications_active'], 'funding', 'boolean');
        Setting::set('registration_fee', $state['registration_fee'], 'funding', 'integer');
        
        // Limits & Fees
        Setting::set('deposit_min', $state['deposit_min'], 'limits', 'integer');
        Setting::set('deposit_max', $state['deposit_max'], 'limits', 'integer');
        Setting::set('deposit_limit_daily', $state['deposit_limit_daily'], 'limits', 'integer');
        
        Setting::set('withdrawal_min', $state['withdrawal_min'], 'limits', 'integer');
        Setting::set('withdrawal_max', $state['withdrawal_max'], 'limits', 'integer');
        Setting::set('withdrawal_limit_daily', $state['withdrawal_limit_daily'], 'limits', 'integer');

        Setting::set('transfer_internal_active', $state['transfer_internal_active'], 'limits', 'boolean');
        Setting::set('transfer_self_active', $state['transfer_self_active'], 'limits', 'boolean');
        Setting::set('transfer_internal_min', $state['transfer_internal_min'], 'limits', 'integer');
        Setting::set('transfer_internal_max', $state['transfer_internal_max'], 'limits', 'integer');
        Setting::set('transfer_internal_fee_percent', $state['transfer_internal_fee_percent'], 'limits', 'float');
        Setting::set('transfer_internal_fee_fixed', $state['transfer_internal_fee_fixed'], 'limits', 'float');
        
        Setting::set('transfer_wire_max', $state['transfer_wire_max'], 'limits', 'integer');
        Setting::set('transfer_domestic_max', $state['transfer_domestic_max'], 'limits', 'integer');
        
        // Referral & Rank
        Setting::set('referral_enabled', $state['referral_enabled'], 'referral', 'boolean');
        Setting::set('referral_bonus', $state['referral_bonus'], 'referral', 'integer');

        // Loans
        Setting::set('loans_enabled', $state['loans_enabled'], 'loans', 'boolean');
        Setting::set('loan_min_amount', $state['loan_min_amount'], 'loans', 'integer');
        Setting::set('loan_max_amount', $state['loan_max_amount'], 'loans', 'integer');
        Setting::set('loan_interest_rate', $state['loan_interest_rate'], 'loans', 'float');
        
        // Payment Gateways
        Setting::set('payment_gateway_mode', $state['payment_gateway_mode'], 'payment');
        Setting::set('payment_auto_gateways_enabled', $state['payment_auto_gateways_enabled'], 'payment', 'boolean');
        Setting::set('payment_manual_gateways_enabled', $state['payment_manual_gateways_enabled'], 'payment', 'boolean');
        
        // Feature Toggles
        Setting::set('feature_deposit', $state['feature_deposit'], 'features', 'boolean');
        Setting::set('feature_withdraw', $state['feature_withdraw'], 'features', 'boolean');
        Setting::set('feature_transfer', $state['feature_transfer'], 'features', 'boolean');
        Setting::set('feature_transfer_internal', $state['feature_transfer_internal'], 'features', 'boolean');
        Setting::set('feature_transfer_own', $state['feature_transfer_own'], 'features', 'boolean');
        Setting::set('feature_transfer_domestic', $state['feature_transfer_domestic'], 'features', 'boolean');
        Setting::set('feature_transfer_wire', $state['feature_transfer_wire'], 'features', 'boolean');
        Setting::set('feature_voucher', $state['feature_voucher'], 'features', 'boolean');
        Setting::set('feature_loans', $state['feature_loans'], 'features', 'boolean');
        Setting::set('feature_grants', $state['feature_grants'], 'features', 'boolean');
        Setting::set('feature_funding_sources', $state['feature_funding_sources'], 'features', 'boolean');
        Setting::set('feature_applications', $state['feature_applications'], 'features', 'boolean');
        Setting::set('feature_kyc', $state['feature_kyc'], 'features', 'boolean');
        Setting::set('feature_support', $state['feature_support'], 'features', 'boolean');
        Setting::set('feature_ranks', $state['feature_ranks'], 'features', 'boolean');
        Setting::set('feature_referrals', $state['feature_referrals'], 'features', 'boolean');
        Setting::set('feature_notifications', $state['feature_notifications'], 'features', 'boolean');
        Setting::set('feature_transactions', $state['feature_transactions'], 'features', 'boolean');
        Setting::set('feature_accounts', $state['feature_accounts'], 'features', 'boolean');
        
        // System
        Setting::set('currency_symbol', $state['currency_symbol'], 'system');
        Setting::set('maintenance_mode', $state['maintenance_mode'], 'system', 'boolean');
        
        Notification::make() 
            ->title('Settings Saved')
            ->success()
            ->send();
    }
}
