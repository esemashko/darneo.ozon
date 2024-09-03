<script>
BX.BitrixVue.component('ozon-product-list', {
    props: {
        data: {
            type: Object,
            required: true
        },
        page: {
            type: Number,
            required: true
        },
        finalPage: {
            type: Boolean,
            required: false
        },
    },
    data: function () {
        return {
            isNextPage: false,
            dataJson: {}
        }
    },
    watch: {
        data: function () {
            this.isNextPage = false
        },
        isNextPage: function (value) {
            if (value && !this.finalPage) {
                this.$emit('actionNextPage', this.page + 1)
            }
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initTable()
            this.initNav()
        })
    },
    destroyed: function () {
        $(this.$el).find('#responsive').DataTable().destroy()
    },
    methods: {
        setDataJson: function (dataJson) {
            this.dataJson = dataJson
        },
        initTable: function () {
            $(this.$el).find('#basic-1').DataTable({
                responsive: true,
                searching: false,
                ordering: false,
                info: false,
                paging: false,
                autoWidth: false
            })
        },
        initNav: function () {
            let vm = this
            let $win = $(window)
            let $marker = $(this.$el).find('#catalog-nav')
            $win.scroll(function () {
                if ($win.scrollTop() + $win.height() >= $marker.offset().top) {
                    if (!vm.isNextPage && !vm.finalPage) {
                        vm.isNextPage = true
                    }
                }
            })
        },
    },
    template: `
        <div class='table-responsive'>
        <table class='table table-row-bordered table-striped table-row-gray-200 align-middle gs-7 gy-4' id='basic-1'>
            <thead>
            <tr class='fw-bold text-muted bg-light'>
                <th class='min-w-100px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_TABLE_HEAD_DATE') }}</th>
                <th class='min-w-100px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_TABLE_HEAD_CODE') }}</th>
                <th class='min-w-125px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_TABLE_HEAD_ITEM') }}</th>
                <th class='min-w-125px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_TABLE_HEAD_SECTION') }}</th>
                <th class='min-w-100px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_TABLE_HEAD_LOG') }}</th>
                <!--<th class='min-w-100px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_LOG_TABLE_HEAD_STATUS') }}</th>-->
            </tr>
            </thead>
            <tbody>
            <ozon-product-list-item
                v-for='item in data.LIST' :key='Number(item.ID)'
                v-bind:item='item'
                v-on:setDataJson='setDataJson'
            />
            </tbody>
        </table>
        <div class='loader-box' v-show='isNextPage'>
            <div class='loader-19'></div>
        </div>
        <div id='catalog-nav'></div>
        <template v-if='dataJson.SEND_JSON'>
            <product-json
                v-bind:data='dataJson'
                v-on:setDataJson='setDataJson'
            />
        </template>
        </div>
    `,
})
</script>