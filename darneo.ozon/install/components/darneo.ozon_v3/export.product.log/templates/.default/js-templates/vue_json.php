<script>
BX.BitrixVue.component('product-json', {
    props: {
        data: {
            type: Object,
            required: true
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.init()
            this.initJson('#sendJson', this.data.SEND_JSON)
            this.initJson('#answer', this.data.ANSWER)
            //this.initJson('#answerJson', this.data.ANSWER_JSON)
        })
    },
    methods: {
        init: function () {
            let vm = this
            $(this.$el).modal('toggle')
            $(this.$el).on('hidden.bs.modal', function () {
                vm.$emit('setDataJson', {})
            })
        },
        initJson: function (selector, data) {
            new JsonEditor($(this.$el).find(selector), data)
        },
    },
    template: `
        <div class='modal fade bd-example-modal-lg'>
            <div class='modal-dialog modal-lg'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title' v-html='data.ELEMENT_NAME'></h5>
                        <button class='btn-close' type='button' data-bs-dismiss='modal' aria-label='Close'></button>
                    </div>
                    <div class='modal-body'>
                        <p>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_DATE_CREATE') }}
                            {{ data.DATE_CREATED }}
                        </p>
                        <div id='sendJson'></div>
                        <hr>
                        <p>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_ANSWER') }}</p>
                        <div id='answer'></div>
                        <!--<hr>
                        <p>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_STATUS_ADD') }}</p>
                        <div id='answerJson'></div>-->
                    </div>
                    <div class='modal-footer'>
                        <a v-if='data.SYSTEM_LOG_LINK.length'
                           :href='data.SYSTEM_LOG_LINK' class='btn btn-primary' target='_blank'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_SYSTEM_LOG') }}
                        </a>
                        <button class='btn btn-secondary' type='button' data-bs-dismiss='modal'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_BUTTON_CLOSE') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `,
})
</script>