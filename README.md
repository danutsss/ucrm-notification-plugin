# UCRM Contract Expire Notification

UCRM plugin that notifies you when client’s signed contract is expiring.

## How can we notify the clients?

We can use the `expiration period` option from the plugin settings and set it to 24 hours to execute the script.

### What the script should do?

The script should check the date when the contract had been signed. The date when the contract had been signed will be saved into a special custom attribute named `signedDate`.

#### How the notifications will be send?

I. Send the first notification to the client when the expiration date is in one month.

II. Send the second notification to the client when the expiration date is in 2 weeks.

III. Send the next notification to the client every day when the expiration date is in 1 week.

#### What we use for plugin development?

-   UCRM API:
    -   Email enqueue [API doc for UISP CRM · Apiary](https://unmscrm.docs.apiary.io/#reference/email/emailidenqueue);
    -   `GET` /clients/`{id}`/attributes [API doc for UISP CRM · Apiary](https://unmscrm.docs.apiary.io/#reference/clients/clientsid/get).
