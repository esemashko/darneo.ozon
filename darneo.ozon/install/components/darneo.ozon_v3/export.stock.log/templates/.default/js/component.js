(function () {
    'use strict'

    BX.namespace('BX.Ozon.ExportLog.Vue')

    BX.Ozon.ExportLog.Vue = {
        init: function (parameters) {
            this.ajaxUrl = parameters.ajaxUrl || ''
            this.signedParams = parameters.signedParams
            this.data = parameters.data || {}

            this.initStore()
            this.initComponent()
        },
        initStore: function () {
            this.store = BX.Vuex.store({
                state: {
                    data: this.data,
                    successList: [],
                    errorList: [],
                },
                actions: {
                    change(store, payload) {
                        store.commit('changeData', payload)
                    }
                },
                mutations: {
                    changeData(state, params) {
                        if (params.data) {
                            state.data = params.data
                        }
                        if (params.errorList) {
                            state.errorList = params.errorList
                        }
                        if (params.successList) {
                            state.successList = params.successList
                        }
                    }
                }
            })
        },

        initComponent: function () {
            BX.BitrixVue.createApp({
                el: '#vue-export-log',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams,
                        request: {
                            isUpdateList: false,
                            isClearList: false,
                        },
                        showSettingLog: false
                    }
                }, this),
                computed:
                        BX.Vuex.mapState({
                            data: state => state.data,
                            errorList: state => state.errorList,
                            successList: state => state.successList,
                            loc: () => BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_'),
                        }),
                watch: {
                    data: function () {
                        this.request.isUpdateList = false
                        this.request.isClearStart = false
                    },
                },
                methods: {
                    setShowSettingLog: function (value) {
                        this.showSettingLog = Boolean(value)
                    },
                    actionSetSettingLog: function (value) {
                        let data = this.getDataAjax()
                        data['count'] = value
                        data['action'] = 'setSettingLog'
                        BX.Ozon.ExportLog.Vue.sendRequest(data)
                    },
                    actionClear: function () {
                        let data = this.getDataAjax()
                        this.request.isUpdateList = true
                        this.request.isClearStart = true
                        data['action'] = 'clear'
                        BX.Ozon.ExportLog.Vue.sendRequest(data)
                    },
                    actionNextPage: function (page) {
                        let data = this.getDataAjax()
                        this.request.isUpdateList = true
                        data['action'] = 'list'
                        data['page'] = page
                        BX.Ozon.ExportLog.Vue.sendRequest(data)
                    },
                    getDataAjax: function () {
                        let data = {}
                        data['signedParamsString'] = this.signedParams
                        return data
                    },
                },
                template: `
                    <div class='card' v-if='data.LIST.length'>
                        <div class='block_disabled' v-show='request.isUpdateList'></div>
                        <div class='card-body'>
                            <div class='d-flex justify-content-between align-items-start flex-wrap mb-2'>
                                <div class='d-flex flex-column my-4 text-muted'>
                                    <div>
                                        <span class='me-1' v-html='loc.DARNEO_OZON_VUE_STOCK_LOG_COUNT_ALL'></span>
                                        <span class='badge badge-light text-muted' v-html='data.COUNT_ALL'></span>
                                    </div>
                                    <div>
                                        <span class='me-1' v-html='loc.DARNEO_OZON_VUE_STOCK_LOG_AUTO_CLEAR'></span>
                                        <a href='javascript:void(0);'
                                           class='btn btn-sm btn-light btn-color-muted btn-active-light-success px-4 py-2 me-4'
                                           v-on:click='showSettingLog=!showSettingLog'>
                                            <i class='ki-duotone ki-calendar fs-3'>
                                                <span class='path1'></span>
                                                <span class='path2'></span>
                                            </i> {{ data.LOG_SAVE }}</a>
                                        <template v-if='showSettingLog'>
                                            <product-modal-log
                                                v-bind:count='Number(data.LOG_SAVE)'
                                                v-on:setShowSettingLog='setShowSettingLog'
                                                v-on:actionSetSettingLog='actionSetSettingLog'
                                            />
                                        </template>
                                    </div>
                                </div>

                                <div class='d-flex my-4'>
                                    <ozon-product-action
                                        v-bind:isClearStart='request.isClearStart'
                                        v-on:actionClear='actionClear'
                                    />
                                </div>
                            </div>
                            <ozon-product-list
                                v-bind:data='data'
                                v-bind:page='Number(data.PAGE)'
                                v-bind:finalPage='Boolean(data.FINAL_PAGE)'
                                v-on:actionNextPage='actionNextPage'
                            />
                        </div>
                        <product-modal
                            v-bind:data='errorList'
                            v-bind:title='loc.DARNEO_OZON_VUE_STOCK_LOG_WARNING'
                        />
                    </div>
                `
            })
        },

        updateList: function () {
            let data = {}
            data['action'] = 'list'
            data['signedParamsString'] = this.signedParams
            BX.Ozon.ExportLog.Vue.sendRequest(data)
        },

        sendRequest: function (data) {
            BX.ajax({
                method: 'POST',
                dataType: 'json',
                url: this.ajaxUrl,
                data: data,
                async: true,
                onsuccess: result => {
                    if (result.DATA_VUE) {
                        this.store.commit('changeData', {
                            data: result.DATA_VUE
                        })
                    }
                    if (result.STATUS === 'ERROR') {
                        if (result.ERROR_LIST && result.ERROR_LIST.length > 0) {
                            this.store.commit('changeData', {
                                successList: [],
                                errorList: result.ERROR_LIST
                            })
                        }
                    }
                },
                onfailure: result => {

                },
            })
        },
    }
})()