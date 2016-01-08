# Toggl Moneybird Integration
This integration aims to export Toggl entries and create a Moneybird invoice based on those entries (you'll need PHP 5.6 -> upgrade people!).

## Screenshots
These screenshots have dummy data in them, 'Contact / Project XX' would be an actual contact / project name.
<img width="1061" alt="screenshot 2016-01-07 21 14 02" src="https://cloud.githubusercontent.com/assets/431360/12181643/cedff39c-b583-11e5-862b-171614ec02f0.png">
<img width="1061" alt="screenshot 2016-01-07 21 14 24" src="https://cloud.githubusercontent.com/assets/431360/12181642/cec3e968-b583-11e5-8991-72ce36ef7232.png">
<img width="1061" alt="screenshot 2016-01-07 21 14 43" src="https://cloud.githubusercontent.com/assets/431360/12181641/ceb8b214-b583-11e5-8a26-c07100b4d340.png">
![screenshot 2016-01-07 21 14 59](https://cloud.githubusercontent.com/assets/431360/12181640/ce7ad4b2-b583-11e5-8de0-7feffe8313cf.png)

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

- **toggl_token** (required) - this can be found in your profile on Toggl.com
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

1. (optional, only when multiple are found) Choose Toggl workspace
2. Choose which project you want to find entries for.
3. From which date do you want to find entries?
4. Until which date do you want to find entries?
5. Choose which time entries you want to invoice.
6. (optional) Confirm you want to invoice items that are bugfixes or have the 'billed' tag in Toggl
7. Choose which contact you want to create the invoice for
8. (optional) Do you want to add the entries to an existing concept invoice for this contact?

Made by @peterjaap / @elgentos