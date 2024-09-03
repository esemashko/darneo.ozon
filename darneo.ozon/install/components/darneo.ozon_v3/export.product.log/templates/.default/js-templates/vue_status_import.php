<script>
    BX.BitrixVue.component('ozon-status-import', {
        props: {
            isImportStart: {
                type: Boolean,
                required: false
            },
            isClearStart: {
                type: Boolean,
                required: false
            },
        },
        methods: {
            actionStart: function () {
                if (!this.isImportStart) {
                    this.$emit('actionImport')
                }
            },
            actionClear: function () {
                if (!this.isClearStart) {
                    this.$emit('actionClear')
                }
            },
        },
        template: `
            <button class='btn btn-danger' type='button' v-on:click='actionClear()' v-bind:disabled='isClearStart'>
            <span v-show='!isClearStart'>
                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_BUTTON_CLEAR') }}
            </span>
                <span v-show='isClearStart'>
                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_BUTTON_WORK') }}
            </span>
                <i class='fa fa-spin fa-spinner' v-show='isClearStart'></i>
            </button>
        `,
    })
</script>