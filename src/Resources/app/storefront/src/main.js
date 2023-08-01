import PluginManager from 'src/plugin-system/plugin.manager';
import QuantitySelectorCustomerPricePlugin from './quantity-selector-customer-price/quantity-selector-customer-price.plugin';

PluginManager.register('QuantitySelectorCustomerPrice', QuantitySelectorCustomerPricePlugin, '[data-quantity-selector-customer-price]');
