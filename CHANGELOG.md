# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added
- `InvoiceTransmitterInterface` port, `InvoiceDocument` and `TransmissionResult`.
- Log of the sends (`tmonier_einvoice_transmission`, one line per invoice) with the minimal statuses `deposited`, `rejected`, `error` and `not_transmitted`, extensible with `additional_statuses`.
- Idempotent listener of `tmonier_facturx.invoice_generated`: a failing adapter or an unknown status is logged as `error` and never breaks the generation of the invoice.
- `NullInvoiceTransmitter` (default) and `RecordingInvoiceTransmitter` test double.
