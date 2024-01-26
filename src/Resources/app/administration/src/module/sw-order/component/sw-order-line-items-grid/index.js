import enGB from "../../../../snippet/en-GB.json";
import deDE from "../../../../snippet/de-DE.json";

Shopware.Component.override('sw-order-line-items-grid', {

    snippets: {
        'en-GB': enGB,
        'de-DE': deDE
    },

    computed: {
        getLineItemColumns() {
            const columns = this.$super('getLineItemColumns');
            columns.splice(1, 0, this.getCustomPartNumberColumn());
            columns.splice(2, 0, this.getPositionCommentColumn());
            return columns;
        },
    },

    methods: {
        getCustomPartNumberColumn() {
            return {
                property: 'payload.strack_order_position_own_part_number',
                dataIndex: 'payload.strack_order_position_own_part_number',
                allowResize: true,
                label: 'strack.sw-order.ownNumber',
                multiline: true,
                primary: false,
                width: '65px'
            }
        },
        getPositionCommentColumn() {
            return {
                property: 'payload.strack_order_position_comment',
                dataIndex: 'payload.strack_order_position_comment',
                allowResize: true,
                label: 'strack.sw-order.comment',
                multiline: true,
                primary: false,
                width: '65px'
            }
        }
    }
});
