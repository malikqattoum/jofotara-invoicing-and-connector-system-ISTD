# Automatic Token Refresh Guide

## Overview

The POS Connector now supports automatic token refresh when authentication tokens expire. This ensures uninterrupted operation without manual intervention.

## How It Works

1. **Token Validation**: Before each API request, the system checks if the token is valid
2. **Expiry Detection**: Tokens are automatically refreshed when they expire or are about to expire (within 30 minutes)
3. **Password Authentication**: Uses stored password to obtain a new token when needed
4. **Seamless Operation**: API requests continue without interruption during token refresh

## Setup

### 1. Add Password to Configuration

Add your password to the `config.json` file:

```json
{
    "base_url": "http://127.0.0.1:8000",
    "email": "your-email@example.com",
    "password": "your-password-here",
    "vendor_id": 2,
    "token": "existing-token...",
    "token_expiry": "2025-07-12T15:52:00.000000",
    ...
}
```

### 2. Using the Setup Helper

You can use the test script to set up the password:

```bash
python test_token_refresh.py --setup-password
```

## Testing

Test the automatic token refresh functionality:

```bash
python test_token_refresh.py
```

This will run comprehensive tests including:
- Initial authentication
- Token validation
- API requests with valid tokens
- Token expiry simulation
- Automatic token refresh
- API requests after refresh
- Token expiring soon detection

## Security Considerations

⚠️ **Important**: The password is stored in plain text in `config.json` for automatic refresh functionality.

**Security recommendations:**
1. Ensure `config.json` has appropriate file permissions (readable only by the application user)
2. Consider using environment variables for sensitive deployments
3. Regularly rotate passwords
4. Monitor access to the configuration file

## Features

### Automatic Token Refresh
- Detects expired tokens automatically
- Refreshes tokens before they expire (30-minute threshold)
- Handles 401 Unauthorized responses gracefully
- Retries failed requests after token refresh

### Token Validation
- Validates tokens before API requests
- Checks expiry time from stored configuration
- Verifies token validity with API calls

### Error Handling
- Graceful fallback when refresh fails
- Detailed logging of authentication events
- User-friendly error messages

## API Methods

### `ensure_valid_token()`
Ensures a valid token is available, refreshing if necessary.

### `refresh_token()`
Manually refresh the authentication token using stored password.

### `is_token_expiring_soon(minutes_threshold=30)`
Check if token will expire within the specified minutes.

### `authenticate(force_refresh=False)`
Authenticate with the API, optionally forcing a refresh.

## Logging

Token refresh events are logged with appropriate levels:
- `INFO`: Successful operations
- `WARNING`: Token validation failures
- `ERROR`: Authentication failures

Check the logs in the `logs/laravel_api.log` file for detailed information.

## Troubleshooting

### Password Not Configured
```
❌ Cannot refresh token: no password stored in configuration
```
**Solution**: Add password to `config.json` or run `python test_token_refresh.py --setup-password`

### Authentication Failed
```
❌ Token refresh failed
```
**Solution**: 
1. Verify password is correct
2. Check API connectivity
3. Ensure Laravel backend is running
4. Verify email/password combination

### API Connection Issues
```
❌ Cannot make API request: failed to obtain valid authentication token
```
**Solution**:
1. Check network connectivity
2. Verify API URL in configuration
3. Ensure Laravel backend is accessible

## Integration

The automatic token refresh is integrated into all API operations:
- `create_invoice()`
- `submit_invoice()`
- `get_invoice_status()`
- `download_invoice_pdf()`

No changes are required to existing code - token refresh happens automatically.
