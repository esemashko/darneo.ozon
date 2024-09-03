<script>
BX.BitrixVue.component('ozon-product-list-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
    },
    methods: {
        showDataJson: function (data) {
            this.$emit('setDataJson', data)
        },
    },
    template: `
        <tr>
        <td v-html='item.DATE_CREATED'></td>
        <td v-html='item.OFFER_ID'></td>
        <td>
            <a v-bind:href='item.ELEMENT_LINK' target='_blank' v-html='item.ELEMENT_NAME'></a>
        </td>
        <td v-html='item.SEND_JSON.stock'></td>
        <td>
            <button class='btn btn-primary' type='button' v-on:click='showDataJson(item)'>
                <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_STOCK_LOG_BUTTON_SHOW') }}</span>
            </button>
        </td>
        <td>
            <span v-if='item.IS_ERROR' class='badge rounded-pill badge-danger' v-html='item.STATUS'></span>
            <span v-else class='badge rounded-pill badge-light text-dark' v-html='item.STATUS'></span>
        </td>
        </tr>
    `,
})
</script>