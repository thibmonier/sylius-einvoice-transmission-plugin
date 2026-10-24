# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added
- Relaunch of a send in error: `RelaunchTransmissionHandler`, `InvoiceDocumentProviderInterface` (option `document_provider`) and `TransmissionNotRelaunchable`.
- `TransmissionJournalInterface::reopen()` (one conditional update, same line, only for the status `error`). A custom journal has to implement it.

### Changed
- `InvoiceTransmissionHandler::deliver()` is public (the sending part of `handle()`, without the claim of the invoice).

## [0.1.0] - 2026-10-24

### Added
- `InvoiceTransmitterInterface` port, `InvoiceDocument` and `TransmissionResult`.
- Log of the sends (`tmonier_einvoice_transmission`, one line per invoice) with the minimal statuses `deposited`, `rejected`, `error` and `not_transmitted`, extensible with `additional_statuses`.
- Idempotent listener of `tmonier_facturx.invoice_generated`: a failing adapter or an unknown status is logged as `error` and never breaks the generation of the invoice.
- `NullInvoiceTransmitter` (default) and `RecordingInvoiceTransmitter` test double.

[Unreleased]: https://github.com/thibmonier/sylius-einvoice-transmission-plugin/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/thibmonier/sylius-einvoice-transmission-plugin/releases/tag/v0.1.0
