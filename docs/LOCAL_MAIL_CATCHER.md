# Local Mail Catcher

BuildLedger is now wired to a local Mailpit inbox for development.

## What runs

- SMTP server: `127.0.0.1:1025`
- Web inbox: `http://localhost:8025`

## Current local backend settings

- `MAIL_MAILER=smtp`
- `MAIL_HOST=127.0.0.1`
- `MAIL_PORT=1025`
- `MAIL_FROM_ADDRESS=hello@buildledger.local`
- `MAIL_FROM_NAME=BuildLedger`

## How to use it

1. Start the local stack with Docker Compose if you want the bundled Mailpit container.
2. Or keep running Laravel through Laragon and Mailpit will still catch mail on `127.0.0.1:1025`.
3. Open `http://localhost:8025` in your browser to read verification and invoice emails.

