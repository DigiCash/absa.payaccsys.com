# How to use the Postman collection

## What is this?

The **ABSA API** Postman collection is a ready-made set of HTTP requests for
the ABSA API hub (the Laravel 13 application in this repository). It lets you
exercise the hub's endpoints without writing code:

- Authenticate against the hub and obtain a Bearer token.
- Call the Statements facade endpoints (`/api/v1/statements/*`) exactly as an
  internal consumer would.

It ships with a companion **ABSA API LOCAL** environment that holds the
variables (base URL, credentials, access token) the requests reference.

## Files in this folder

| File | What it is |
| --- | --- |
| `ABSA API.postman_collection.json` | The collection — the requests, grouped into folders (AUTH, StatementsAPI). |
| `ABSA API LOCAL.postman_environment.json` | A Postman environment — the variables used by the requests. |
| `README.md` | This guide. |

> The folder is versioned so the collection and environment travel with the
> repository.

## What everything means

### Collection layout

| Folder / request | Method & path (with the default `APP_URL`) | Purpose |
| --- | --- | --- |
| **AUTH** → Login | `POST …/api/v1/login` | Exchanges a user's email + password for an `access_token`. |
| **AUTH** → Check User | `GET …/api/v1/user` | Returns the authenticated user for the current token. |
| **StatementsAPI** → Get Balances | `GET …/api/v1/statements/balances` | List balances across accounts. |
| **StatementsAPI** → Get Account Balance | `GET …/api/v1/statements/accounts/{accountId}/balances` | Balance for one account. |
| **StatementsAPI** → Get All Statements | `GET …/api/v1/statements` | List all statements. |
| **StatementsAPI** → Get Account Statements | `GET …/api/v1/statements/accounts/{accountId}/statements` | Statements for one account. |
| **StatementsAPI** → Get Statement | `GET …/api/v1/statements/accounts/{accountId}/statements/{statementId}` | One statement. |
| **StatementsAPI** → Get Statement Transactions | `GET …/api/v1/statements/accounts/{accountId}/statements/{statementId}/transactions` | Transactions for a statement. |
| **StatementsAPI** → Get Intraday Statement | `GET …/api/v1/statements/accounts/{accountId}/intraday-statement` | Intraday statement for an account. |
| **Get System Health** | `GET …/api/v1/statements/health` | Upstream health check. |

### Authentication

- The collection is configured with **Bearer token** auth at the collection
  level: every request automatically sends
  `Authorization: Bearer {{ACCESS_TOKEN}}`.
- `Login` is the exception — it explicitly uses **no authentication**, since
  it is the request that issues the token.
- The `Login` request has a **test script** that runs after the response and
  stores the returned `access_token` into the `ACCESS_TOKEN` environment
  variable automatically. No copy-pasting of tokens is needed.

### Path variables

`{accountId}` and `{statementId}` are **path parameters** — replace them with
real values before sending (accounts/statements existent in the ABSA
environment you are pointing at). They are plain text placeholders, not
Postman collection variables.

### Environment variables (`ABSA API LOCAL`)

| Variable | Example value | What it means |
| --- | --- | --- |
| `APP_URL` | `http://absa84.payaccsys.local:8089/api/v1` | Base URL of the hub API — **includes the `/api/v1` prefix**. Change only the scheme/host/port to point at a different deployment. |
| `USER_EMAIL` | `user@example.com` | Email of the user you will log in with. |
| `USER_PASSWORD` | — | Password of that user. |
| `ACCESS_TOKEN` | *(set automatically)* | The token returned by `Login`. It is populated by the Login test script; you normally do not set it by hand. |

## How to use it

1. **Import the collection and environment.**
   In Postman: *File → Import*, select both
   `ABSA API.postman_collection.json` and
   `ABSA API LOCAL.postman_environment.json` (or drag them in).
2. **Select the environment.**
   Top-right of Postman, choose **ABSA API LOCAL** from the environment
   dropdown.
3. **Set your variables.**
   Open the environment (the "eye" icon) and set `APP_URL` (if it does not
   match where the app runs) and your user's `USER_EMAIL` / `USER_PASSWORD`.
4. **(Optional) Create a user for the API** — see the section below if you
   need a fresh set of credentials.
5. **Run `AUTH → Login`.**
   Send it. On success the test script writes the token into `ACCESS_TOKEN`
   automatically. You can verify this in the environment variables.
6. **Call the Statements facade.**
   Open any **StatementsAPI** request, fill in the `{accountId}` /
   `{statementId}` placeholders, and press **Send**. Successful responses
   follow the ABSA envelope shape (`Data` / `Links` / `Meta`), with `Data`
   holding the typed collection for the operation.

### Typical flow

```
Login  →  Check User  →  Get Balances  →  Get Account Balance
                                  ↘
        Get All Statements → Get Account Statements → Get Statement
                                  ↘
        Get Statement Transactions / Get Intraday Statement
```

## Creating a User for Postman Collection

### 1. Create User and password using tinker

#### 1.1 Log into Docker
```bash
docker exec -it absa84_api php artisan tinker
```

#### 1.2 Create User
Create a user and ensure that the name, email and password will be copied
```bash
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::create([
    'name'     => 'API User',
    'email'    => 'user@example.com',
    'password' => Hash::make('YourSecurePassword123!'),
]);
```

Then put the created **email** and **password** into `USER_EMAIL` /
`USER_PASSWORD` in the Postman environment and run `AUTH → Login`.

## Troubleshooting

| Symptom | Likely cause / fix |
| --- | --- |
| `401 Unauthorized` on Statement requests | Token missing/expired — run `AUTH → Login` again to refresh `ACCESS_TOKEN`. |
| `404 Not Found` | Check `APP_URL` includes `/api/v1` and the path matches a real route (`php artisan route:list`). |
| Login fails with "invalid credentials" | Email/password placeholders not resolving or credentials don't match a user — see the environment note above, then re-create the user if needed. |
| `Connection refused / DNS` | `APP_URL` host/port does not point at the running app — align it with the container's published port. |
