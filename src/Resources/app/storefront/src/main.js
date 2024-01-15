import PluginManager from 'src/plugin-system/plugin.manager';
import QuantitySelectorCustomerPricePlugin from './quantity-selector-customer-price/quantity-selector-customer-price.plugin';
import CartExporterPlugin from "./cart-exporter-plugin/cart-exporter-plugin";

PluginManager.register('QuantitySelectorCustomerPrice', QuantitySelectorCustomerPricePlugin, '[data-quantity-selector-customer-price]');
PluginManager.register('CartExporter', CartExporterPlugin, '[data-strack-cart-export-button]')