# Toggl Moneybird Integration
This integration aims to export Toggl entries and create a Moneybird invoice based on those entries.

## Installation
```
$ git clone https://github.com/peterjaap/TogglMoneybirdIntegration
$ cd TogglMoneybirdIntegration
$ cp config.yml.sample config.yml
$ vim config.yml (see Configuration for more info)
$ composer install
```
## Configuration
The config.yml file takes a few inputs;

- **toggl_token** (required) - this can be found under your profile on Toggl.com
- **moneybird_administration_id** (required)  - this is the first number in your Moneybird URL when you are logged in to your administration
- **moneybird_access_token** (required)  - this can be generated at https://moneybird.com/user/applications/new (choose the API token for Personal Use and select your administration)
- **hourly_rate** (required) - your hourly rate, to calculate the prices
- **round_to** (optional) - round your time entries to the nearest X minutes (leave empty or set to 0 to disable rounding)
- **moneybird_vat_outside_eu** (optional) - if this is set, this tax rate ID is used for invoices that are sent outside the EU
- **moneybird_vat_inside_eu** (optional) - if this is set, this tax rate ID is used for invoices that are sent inside the EU (excluding NL)

## Usage
```
$ php application.php
```
The application will ask you a number of inputs, in succession;
0. (optional, only when multiple are found) Choose Toggl workspace
1. Choose which project you want to find entries for.
2. From which date do you want to find entries?
3. Until which date do you want to find entries?
4. Choose which time entries you want to invoice.
5. (optional) Confirm you want to invoice items that are bugfixes or have the 'billed' tag in Toggl
6. Choose which contact you want to create the invoice for
7. (optional) Do you want to add the entries to an existing concept invoice for this contact?

Made by @peterjaap / @elgentos