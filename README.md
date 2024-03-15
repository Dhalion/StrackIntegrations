# Strack Integrations

Shopware 6 Plugins for Strack Integrations with extern services


## Live price requests


## Order requests

### Example filter call

### Order list

```frontend.b2b.orders.index```

```
/b2border?documentType=1&orderFrom=2023-01-01&orderTo=2025-01-01
```

#### Params

- documentType: ENUM [0, 1] - 0 = offer, 1 = order, default: 1
- orderFrom - string (Y-m-d), optional
- orderTo - string (Y-m-d), optional


### Order items
```frontend.b2b.orders.order-items```

#### Params
- documentType: ENUM [0, 1] - 0 = offer, 1 = order, default: 1
- orderNumber: string, required 

### Test mode:

You can activate test mode in the plugin config (for the order API).

Example customer number: ```10017```

