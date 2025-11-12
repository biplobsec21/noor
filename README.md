# Quran API Laravel Integration

Laravel implementation for the Quran Foundation API with OAuth2 authentication, caching, and proper error handling.

## Quick Start

### 1. Installation

```bash
# Install dependencies
composer install

# Set up environment variables
cp .env.example .env
```

### 2. Environment Configuration

Add to your `.env` file:

```env
QURAN_API_BASE_URL=https://api.quran.foundation
QURAN_OAUTH_URL=https://oauth.quran.foundation
QURAN_CLIENT_ID=your_client_id
QURAN_CLIENT_SECRET=your_client_secret
```

### 3. Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\QuranApiServiceProvider::class,
],
```

### 4. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

## API Endpoints

### List All Chapters
```http
GET /api/v1/quran/chapters?language=en
```

### Get Single Chapter
```http
GET /api/v1/quran/chapters/{id}?language=en
```

### Get Chapter Info
```http
GET /api/v1/quran/chapters/{id}/info?language=en&locale=en_US
```

### Search Chapters
```http
GET /api/v1/quran/chapters-search?q=faith&language=en
```

## Project Structure

```
App/
├── Config/
│   └── quran.php                    # Configuration file
├── DTOs/QuranApi/
│   ├── Chapter.php                  # Chapter data object
│   ├── ChapterDetail.php            # Single chapter response
│   ├── ChapterInfo.php              # Chapter metadata
│   ├── ChaptersResponse.php         # Chapters list wrapper
│   └── TranslatedName.php           # Translated name object
├── Services/QuranApi/
│   ├── AuthService.php              # OAuth2 authentication
│   └── ChapterService.php           # Chapter operations
└── Http/Controllers/Api/V1/
    └── ChapterController.php        # REST endpoints
```

## Key Features

- ✅ OAuth2 Client Credentials authentication
- ✅ Automatic token refresh and caching
- ✅ Multi-language support
- ✅ Comprehensive caching (24h for data, 3500s for tokens)
- ✅ Search functionality
- ✅ Proper error handling and logging
- ✅ Type-safe DTOs

## Usage Examples

### In Controllers

```php
use App\Services\QuranApi\ChapterService;

public function index(ChapterService $service)
{
    $chapters = $service->getChapters('en');
    return response()->json($chapters);
}
```

### Force Cache Refresh

```http
GET /api/v1/quran/chapters?refresh=true
```

## Response Format

### Success Response
```json
{
    "data": [
        {
            "id": 1,
            "name_simple": "Al-Fatihah",
            "name_arabic": "الفاتحة",
            "verses_count": 7,
            "translated_name": {
                "language_name": "english",
                "name": "The Opener"
            }
        }
    ],
    "meta": {
        "count": 114,
        "language": "en",
        "timestamp": "2023-11-11T17:16:13.000000Z"
    }
}
```

### Error Response
```json
{
    "error": "Invalid chapter ID. Must be between 1 and 114."
}
```

## Caching Strategy

| Resource | Cache Key | TTL |
|----------|-----------|-----|
| Access Token | `quran_api_access_token` | 3500s |
| Chapters List | `quran_chapters_{language}` | 24h |
| Single Chapter | `quran_chapter_{id}_{language}` | 24h |
| Chapter Info | `quran_chapter_info_{id}_{language}_{locale}` | 24h |

## Troubleshooting

### Validate Configuration
```bash
php artisan quran:validate-config
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

### Common Issues

**401 Authentication Error**
- Verify `QURAN_CLIENT_ID` and `QURAN_CLIENT_SECRET`
- Check if credentials are correct in OAuth provider

**Missing Environment Variables**
- Run `php artisan config:clear`
- Ensure all required variables are in `.env`

**Cache Issues**
- Clear cache: `php artisan cache:clear`
- Force refresh: Add `?refresh=true` to requests

## Testing

### Debug Endpoints

Add to `routes/api.php` for testing:

```php
Route::prefix('debug')->group(function () {
    Route::get('config', function () {
        return response()->json([
            'base_url' => config('quran.api.base_url'),
            'has_credentials' => !empty(config('quran.credentials.client_id')),
        ]);
    });
    
    Route::get('token', function (App\Services\QuranApi\AuthService $auth) {
        $token = $auth->getAccessToken();
        return response()->json(['has_token' => !empty($token)]);
    });
});
```

## Requirements

- PHP 8.0+
- Laravel 9.0+
- GuzzleHTTP client

## Security

- Never commit `.env` file
- Store credentials securely
- Use environment variables for sensitive data
- Tokens are automatically cached and refreshed

## License

[Your License Here]

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Review API documentation: [Quran Foundation API Docs]
- Open an issue on GitHub
