import PluginManager from 'src/plugin-system/plugin.manager';
import QuantitySelectorCustomerPricePlugin from './quantity-selector-customer-price/quantity-selector-customer-price.plugin';
import AgiqonB2bOrderListPlugin from './plugin/agiqon-b2b-order-list/agiqon-b2b-order-list.plugin';

PluginManager.register('QuantitySelectorCustomerPrice', QuantitySelectorCustomerPricePlugin, '[data-quantity-selector-customer-price]');
PluginManager.register('AgiqonB2bOrderList', AgiqonB2bOrderListPlugin, '[data-b2b-order-list]');

if (module.hot) {
    module.hot.accept();
}
