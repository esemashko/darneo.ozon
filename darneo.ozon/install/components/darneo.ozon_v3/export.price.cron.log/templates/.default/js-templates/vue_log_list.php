<script>
BX.BitrixVue.component('ozon-log-list', {
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
            isNextPage: false
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
                <th class='w-50'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_CRON_LOG_TABLE_HEAD_DATE_START') }}</th>
                <th class='w-50'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_CRON_LOG_TABLE_HEAD_DATE_FINISH') }}</th>
            </tr>
            </thead>
            <tbody>
            <ozon-log-list-item
                v-for='item in data.LIST' :key='Number(item.ID)'
                v-bind:item='item'
            />
            </tbody>
        </table>
        <div class='loader-box' v-show='isNextPage'>
            <div class='loader-19'></div>
        </div>
        <div id='catalog-nav'></div>
        </div>
    `,
})
</script>