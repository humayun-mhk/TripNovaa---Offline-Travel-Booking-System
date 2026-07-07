# TripNovaa - Offline Travel Booking System

TripNovaa is a localhost PHP/MySQL travel booking application built for an assignment/demo environment. It combines customer travel booking, captain ride management, local ride messaging, payments, rewards, offers, feedback, and an admin control panel in one XAMPP-friendly project.

The application is intentionally simple to run: the active project now uses only one PHP application file, `index.php`, backed by one MySQL database file, `tripnovaa.sql`.

## Table Of Contents

- [Project Summary](#project-summary)
- [Tech Stack](#tech-stack)
- [Project Files](#project-files)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Demo Access](#demo-access)
- [Application Roles](#application-roles)
- [Customer Features](#customer-features)
- [Captain Features](#captain-features)
- [Admin Features](#admin-features)
- [Ride Request Flow](#ride-request-flow)
- [Localhost Messaging Flow](#localhost-messaging-flow)
- [Payments, Offers, Rewards, And Feedback](#payments-offers-rewards-and-feedback)
- [Database Overview](#database-overview)
- [Route And Page Map](#route-and-page-map)
- [Important Implementation Notes](#important-implementation-notes)
- [Troubleshooting](#troubleshooting)
- [Development Checklist](#development-checklist)

## Project Summary

TripNovaa supports three main roles:

- Customer/User: books rides, hotels, trains, buses, restaurants, tickets, tours, sends captain ride requests, pays demo payments, chats locally, and gives feedback.
- Captain: receives user trip requests, accepts or rejects rides, views current trips, opens passenger details, chats with users, tracks earnings, rewards, offers, and profile.
- Admin: monitors users, captains, rides, bookings, payments, offers, rewards, and feedback from management pages.

The system is designed to work on localhost using XAMPP. User-to-captain ride requests and messages are stored in MySQL, so they remain available when switching between user and captain accounts on the same local database.

## Tech Stack

- PHP: Core backend and page rendering.
- MySQL/MariaDB: Data storage.
- PDO: Database access.
- HTML/CSS/JavaScript: Frontend UI.
- Leaflet: Demo map rendering.
- XAMPP: Recommended local server stack.

No Composer, Node.js, Laravel, React, or build step is required.

## Project Files

```text
TRIPNOVAA/
  index.php          Main application file. Contains routing, handlers, UI, CSS, and JS.
  tripnovaa.sql      Database schema and seed/demo data.
  README.md          Project documentation.
```

Runtime entry point:

```text
http://localhost/TRIPNOVAA/index.php
```

## Requirements

- XAMPP or a similar Apache/PHP/MySQL stack.
- PHP 8.0 or newer recommended.
- MySQL or MariaDB.
- Browser such as Chrome, Edge, Firefox, or Brave.
- Apache and MySQL services running in XAMPP.

## Installation

1. Copy the project folder into XAMPP:

```text
C:\xampp\htdocs\TRIPNOVAA
```

2. Start XAMPP services:

- Apache
- MySQL

3. Create the database:

```sql
CREATE DATABASE tripnovaa_db;
```

4. Import the SQL file:

Use phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Then:

- Select or create `tripnovaa_db`
- Open the Import tab
- Choose `tripnovaa.sql`
- Run the import

Or use MySQL CLI:

```bash
mysql -u root tripnovaa_db < tripnovaa.sql
```

5. Open the app:

```text
http://localhost/TRIPNOVAA/index.php
```

## Configuration

Database configuration is at the top of `index.php`:

```php
const DB_HOST = 'localhost';
const DB_NAME = 'tripnovaa_db';
const DB_USER = 'root';
const DB_PASS = '';
```

Default XAMPP uses:

- Host: `localhost`
- User: `root`
- Password: empty
- Database: `tripnovaa_db`

If your MySQL password is different, update `DB_PASS`.

## Demo Access

### Admin

The admin login screen shows the seeded admin credentials:

```text
Email:    admin@tripnovaa.com
Password: admin123
```

### Demo OTP

All user and captain login/register OTP verification uses:

```text
123456
```

### Seeded Database Accounts

The SQL file seeds demo users and captains for realistic database records.

Seeded users:

- Sara Khan: `sara@tripnovaa.com`
- Ali Raza: `ali@tripnovaa.com`

Seeded captains:

- Ahmed Captain: `ahmed.captain@tripnovaa.com`
- Bilal Rider: `bilal.rider@tripnovaa.com`
- Zain Captain: `zain.captain@tripnovaa.com`

For reliable demo testing, you can also register a fresh customer or captain from the app and verify with OTP `123456`.

## Application Roles

### 1. Customer/User

Customers can:

- Register and log in.
- Verify OTP.
- View the mobile customer dashboard.
- Book quick rides.
- Select a captain and send trip requests.
- Track request status.
- Chat with the selected captain.
- Book hotels, trains, buses, restaurants, tours, and tickets.
- View all bookings.
- Make demo payments.
- Apply offers.
- Earn rewards.
- Submit feedback.
- Manage profile.

### 2. Captain

Captains can:

- Register and log in.
- Verify OTP.
- View the captain dashboard.
- Receive trip requests from users.
- Accept or reject trip requests.
- View current, ongoing, and completed trips.
- Open trip details.
- Open passenger details.
- Chat with users through localhost MySQL messages.
- View earnings, wallet, rewards, offers, and profile.

### 3. Admin

Admins can:

- Log in with seeded admin account.
- View platform dashboard.
- Manage users.
- Manage captains.
- View and update rides.
- View hotel bookings.
- View train bookings.
- View bus bookings.
- View restaurant bookings.
- View ticket bookings.
- View payments.
- Create and manage offers.
- View feedback.

## Customer Features

### Customer Dashboard

The customer dashboard includes:

- Quick Ride panel.
- Search bar.
- Travel service shortcuts.
- Popular destinations.
- TripNovaa service grid.
- Offers.
- Recently searched items.
- Booking and reward summary.
- Bottom navigation for Home, My Booking, Plan, Messages, and Profile.

### Quick Ride Booking

The ride flow includes:

1. Search / ride form
2. Captain selection
3. Request status
4. Payment
5. Completed ride / feedback

Fields:

- Pickup location
- Drop location
- Ride type
- Travel date
- Travel time

Ride types currently include:

- Bike
- Car
- Premium Car
- Mini Bus

Fare is calculated using demo fixed fare values.

### Captain Selection

After creating a ride, the user opens Available Captains and taps `Send Request`.

That action:

- Saves the selected `captain_id` on the ride.
- Sets ride status to `captain_selected`.
- Updates `requested_at`.
- Redirects the user to ride tracking.

The selected captain then sees the request in their Trip Requests page.

### My Bookings

My Bookings shows grouped records for:

- Rides
- Hotels
- Train tickets
- Bus tickets
- Restaurants
- Tours/tickets

Each card displays booking details, status, payment status, and relevant actions.

### User Profile

The profile screen displays:

- Customer identity
- Saved address/city
- Rewards
- Booking shortcuts
- Logout

## Captain Features

### Captain Dashboard

The captain dashboard includes:

- Online status card
- Earnings summary
- Quick actions
- Upcoming pickups
- Pending, accepted, rating, and earnings stats
- Bottom navigation for Home, Trips, Earning, Rewards, and Profile

### Trip Requests

Trip Requests shows rides assigned to the logged-in captain with either:

- `pending`
- `captain_selected`

The request card includes:

- Destination/title
- Date
- Distance
- Pickup
- Fare
- Advance amount
- Reject action
- View Details action

### Accepting Or Rejecting

When the captain accepts:

- Ride status becomes `accepted`
- `accepted_at` is set
- Captain availability becomes `busy`
- The captain is redirected to advance payment/confirmation flow

When the captain rejects:

- Ride status becomes `rejected`

### Current Trips

Current Trips includes accepted and ongoing rides.

Captains can mark accepted/ongoing rides as completed. Completing a ride:

- Sets ride status to `completed`
- Sets payment status to `paid`
- Sets `completed_at`
- Adds reward points to the customer
- Creates a demo payment record if none exists
- Sets captain availability back to `available`

### Captain Chat

Captain chat loads messages for the selected ride and captain.

Messages are saved in `ride_messages`, so they persist locally in MySQL.

## Admin Features

### Admin Dashboard

The admin dashboard includes platform counters for:

- Total Users
- Total Captains
- Total Rides
- Total Hotel Bookings
- Total Train Bookings
- Total Bus Bookings
- Total Restaurant Bookings
- Total Ticket Bookings
- Total Payments
- Total Offers
- Total Rewards

It also includes a management page grid for:

- Users
- Captains
- Rides
- Hotels
- Trains
- Buses
- Restaurants
- Tickets
- Payments
- Offers
- Feedback

### Admin Management Pages

Management pages render live database tables. Depending on the page, admins can:

- Update captain account status.
- Update ride status.
- Add offers.
- Update offer status.
- Review bookings, payments, and feedback.

Captain statuses:

- pending
- active
- approved
- inactive
- blocked

Ride statuses:

- pending
- captain_selected
- accepted
- rejected
- ongoing
- completed
- cancelled

Offer statuses:

- active
- inactive
- expired

## Ride Request Flow

This is the main user-to-captain flow:

```text
User books ride
  -> rides row is created with captain_id = NULL and status = pending

User selects captain and taps Send Request
  -> captain_id is saved
  -> status becomes captain_selected
  -> requested_at is updated

Captain logs in
  -> Trip Requests reads pending/captain_selected rides for that captain

Captain accepts
  -> status becomes accepted
  -> accepted_at is set
  -> captain availability becomes busy

Captain completes ride
  -> status becomes completed
  -> payment_status becomes paid
  -> completed_at is set
  -> user reward points are added
```

Important functions involved:

- `handle_book_ride()`
- `handle_select_captain()`
- `fetch_captain_rides()`
- `captain_ride_count()`
- `handle_captain_ride_action()`
- `page_captain_ride_requests()`
- `page_captain_current_trips()`

## Localhost Messaging Flow

The messaging system is offline/localhost database messaging.

It does not use sockets, push notifications, email, SMS, or external chat APIs.

Flow:

```text
User books ride
  -> user selects captain
  -> ride has user_id + captain_id

User opens Messages
  -> user can send message for that ride

Captain opens Messages / Trip Chat
  -> captain sees the user's message

Captain replies
  -> user sees the reply after page refresh/navigation
```

Messages are stored in:

```text
ride_messages
```

Important functions:

- `ensure_ride_messages_table_ready()`
- `handle_send_ride_message()`
- `fetch_ride_messages()`
- `fetch_user_chat_ride()`
- `fetch_captain_chat_ride()`
- `fetch_captain_message_threads()`
- `render_ride_messages()`
- `page_driver_chat()`
- `page_trip_messages()`
- `page_captain_trip_chat()`

## Payments, Offers, Rewards, And Feedback

### Payments

Payments are demo/local records, not real payment gateway transactions.

Payment records include:

- Booking type
- Amount
- Currency
- Provider
- Method
- Cashfree-style order ID
- Transaction ID
- Payment status
- Paid timestamp

### Offers

Seeded offers:

- `TRIP10`: 10% off ride bookings
- `HOTEL20`: 20% off hotel bookings
- `BUS50`: flat Rs. 50 off bus bookings
- `TICKET15`: 15% off ticket bookings

Admin can create more offers.

### Rewards

Rewards are stored in the `rewards` table and also reflected in user reward points.

Examples:

- Signup bonus
- Ride completion reward
- Ticket booking reward

### Feedback

Feedback supports:

- Ride feedback
- App feedback

Ride feedback can update captain rating averages.

## Database Overview

The database name expected by the app is:

```text
tripnovaa_db
```

Main tables in `tripnovaa.sql`:

| Table | Purpose |
| --- | --- |
| `users` | Customer accounts, profile, OTP status, reward points |
| `captains` | Captain accounts, vehicle info, location, status, rating |
| `admins` | Admin login accounts |
| `otp_verifications` | Demo OTP storage |
| `rides` | Ride bookings, captain assignment, status, fare, payment status |
| `ride_messages` | Local user/captain chat messages |
| `hotels` | Hotel catalog |
| `hotel_bookings` | Hotel booking records |
| `trains` | Train catalog |
| `train_bookings` | Train booking records |
| `buses` | Bus catalog |
| `bus_bookings` | Bus booking records |
| `restaurants` | Restaurant catalog |
| `restaurant_bookings` | Restaurant booking records |
| `ticket_events` | Tour/event/ticket catalog |
| `ticket_bookings` | Ticket booking records |
| `payments` | Demo payment history |
| `offers` | Promo/discount offers |
| `rewards` | Reward point history |
| `feedback` | Customer feedback |

### Important Ride Columns

| Column | Meaning |
| --- | --- |
| `user_id` | Customer who created the ride |
| `captain_id` | Selected captain, nullable before selection |
| `pickup_location` | Pickup text |
| `drop_location` | Destination text |
| `ride_type` | Bike/car/premium/mini bus etc. |
| `travel_date` | Requested ride date |
| `travel_time` | Requested ride time |
| `fare` | Demo fare |
| `payment_status` | unpaid, paid, refunded |
| `status` | pending, captain_selected, accepted, rejected, ongoing, completed, cancelled |
| `requested_at` | Time request was sent to captain |
| `accepted_at` | Time captain accepted |
| `completed_at` | Time ride completed |

## Route And Page Map

### Public / Entry Pages

- `splash`
- `get-started`
- `role-selection`
- `user-register`
- `user-login`
- `captain-register`
- `captain-login`
- `admin-login`
- `otp`

### Customer Pages

- `user-dashboard`
- `book-ride`
- `available-captains`
- `ride-confirm`
- `ride-tracking`
- `ride-success`
- `feedback`
- `feedback-success`
- `payment`
- `payment-success`
- `payment-failed`
- `hotel-search`
- `hotel-list`
- `hotel-book`
- `hotel-success`
- `train-search`
- `train-list`
- `train-book`
- `train-success`
- `bus-search`
- `bus-list`
- `bus-book`
- `bus-success`
- `restaurant-search`
- `restaurant-list`
- `restaurant-book`
- `restaurant-success`
- `plan-trip`
- `plan-trip-transport`
- `plan-trip-options`
- `plan-trip-detail`
- `plan-trip-captain`
- `plan-trip-arrival`
- `plan-trip-accepted`
- `plan-trip-deposit`
- `plan-trip-guide`
- `plan-trip-complete`
- `plan-trip-reminder`
- `group-tours`
- `group-tour-details`
- `group-tour-captain`
- `group-tour-seats`
- `group-tour-advance`
- `group-tour-confirmed`
- `group-tour-booking`
- `group-tour-itinerary`
- `group-tour-during`
- `group-tour-remaining`
- `group-tour-completed`
- `group-tour-more`
- `tour-ticket-search`
- `tour-ticket-results`
- `tour-ticket-book`
- `ticket-success`
- `my-bookings`
- `driver-chat`
- `user-profile`
- `rewards-offers`
- `apply-offer`

### Captain Pages

- `captain-dashboard`
- `captain-ride-requests`
- `captain-current-trips`
- `captain-completed-trips`
- `captain-trip-details`
- `captain-accept-trip`
- `captain-advance-payment`
- `captain-navigation`
- `captain-trip-progress`
- `captain-trip-earnings`
- `captain-passenger-details`
- `captain-trip-chat`
- `captain-trip-history`
- `captain-wallet`
- `captain-profile`
- `captain-earnings-analytics`
- `captain-earnings`
- `captain-rewards`
- `captain-offers`
- `trip-messages`
- `my-trips-posted`
- `post-new-trip`
- `driver-offers`
- `saved-trips`

### Admin Pages

- `admin-dashboard`
- `admin-users`
- `admin-captains`
- `admin-rides`
- `admin-hotel-bookings`
- `admin-train-bookings`
- `admin-bus-bookings`
- `admin-restaurant-bookings`
- `admin-ticket-bookings`
- `admin-payments`
- `admin-offers`
- `admin-feedback`

## Important Implementation Notes

- The active runtime is only `index.php` plus the imported `tripnovaa.sql` database.
- `index.php` contains routing, handlers, database helpers, CSS, JavaScript, and page rendering.
- `tripnovaa.sql` contains schema and seeded demo data.
- Authentication is session-based.
- OTP is demo-only and always uses `123456`.
- Admin login uses a seeded database account.
- User/captain messaging works through MySQL, not real-time sockets.
- Payments are demo records and do not charge real money.
- Some UI assets use external CDN/image URLs such as Leaflet and Unsplash. Core database features still run locally, but full visual/map rendering may depend on browser network availability unless assets are vendored locally.
- The app includes compatibility helpers that alter older local tables when required, for example ride status and message table readiness.
- Current UI is mobile-app styled with top/bottom navigation adjusted for user, captain, and admin roles.

## Troubleshooting

### Database connection issue

Check:

- XAMPP MySQL is running.
- Database name is `tripnovaa_db`.
- `tripnovaa.sql` was imported.
- Credentials in `index.php` match your local MySQL setup.

### Admin login fails

Use:

```text
admin@tripnovaa.com
admin123
```

If it still fails:

- Re-import `tripnovaa.sql`.
- Confirm the `admins` table has the seeded row.

### OTP fails

Use:

```text
123456
```

### Captain request does not appear

Check the full flow:

1. Log in as user.
2. Book Ride.
3. Choose a captain.
4. Tap Send Request.
5. Log in as that same captain.
6. Open Trip Requests.

The ride must have:

- `captain_id` set to the selected captain
- `status` equal to `pending` or `captain_selected`

### Messages do not appear

Messages require:

- A ride with a selected captain.
- A valid `user_id`.
- A valid `captain_id`.
- The `ride_messages` table.

If the table is missing, re-import SQL or visit a page that triggers `ensure_ride_messages_table_ready()`.

### Page looks overlapped near bottom navigation

Recent UI fixes added extra bottom spacing for:

- User booking screens
- Captain screens
- Admin dashboard and management pages

If browser cache keeps old CSS, hard refresh:

```text
Ctrl + F5
```

### PHP syntax check

Run:

```bash
php -l index.php
```

Expected:

```text
No syntax errors detected in index.php
```

## Development Checklist

Before submitting or presenting:

- Apache is running.
- MySQL is running.
- `tripnovaa_db` exists.
- `tripnovaa.sql` imported successfully.
- `index.php` passes syntax check.
- Admin login works.
- User registration/login works with OTP `123456`.
- Captain registration/login works with OTP `123456`.
- User can book a ride and send a captain request.
- Captain can see the request in Trip Requests.
- Captain can accept/reject the request.
- User/captain chat saves messages.
- My Bookings shows ride and travel bookings.
- Admin pages show database tables.

## Suggested Demo Script

1. Open `http://localhost/TRIPNOVAA/index.php`.
2. Register or log in as a user.
3. Verify OTP with `123456`.
4. Open Quick Ride / Book Ride.
5. Enter pickup and drop.
6. Choose ride type, date, and time.
7. Tap Find Captains.
8. Select a captain and tap Send Request.
9. Open Messages to see chat availability.
10. Log out and log in as the selected captain.
11. Verify OTP with `123456`.
12. Open Trip Requests.
13. Accept the request.
14. Open Trip Chat and send a reply.
15. Log back in as user and confirm the message appears.
16. Log in as admin and review the ride in Admin Rides.

## License / Usage

This project is intended for educational, assignment, and local demonstration use. Real production deployment would require stronger authentication, CSRF protection, validation hardening, real payment integration, real-time messaging, asset bundling, and environment-based configuration.
