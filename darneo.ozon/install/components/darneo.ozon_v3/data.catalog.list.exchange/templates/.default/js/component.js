(function () {
    'use strict'

    BX.namespace('BX.Ozon.ImportExchange.Vue')

    BX.Ozon.ImportExchange.Vue = {
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
                    isImportStart: false
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
                        if (params.isImportStart !== undefined) {
                            state.isImportStart = params.isImportStart
                        }
                    }
                }
            })
        },

        initComponent: function () {
            BX.BitrixVue.createApp({
                el: '#vue-import-exchange',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams,
                    }
                }, this),
                computed:
                        BX.Vuex.mapState({
                            data: state => state.data,
                            errorList: state => state.errorList,
                            successList: state => state.successList,
                            isImportStart: state => state.isImportStart,
                        }),
                watch: {
                    data: function () {
                        if (this.data.FINISHED === true) {
                            BX.Ozon.ImportExchange.Vue.store.commit('changeData', { isImportStart: false })
                        }
                    },
                    isImportStart: function (val, old) {
                        if (old && !val) {
                            BX.Ozon.DataCatalogList.Vue.updateList()
                        }
                    },
                },
                methods: {
                    actionStart: function (page) {
                        let data = this.getDataAjax()
                        data['action'] = 'start'
                        data['page'] = page
                        BX.Ozon.ImportExchange.Vue.sendRequest(data)
                    },
                    getDataAjax: function () {
                        let data = {}
                        data['signedParamsString'] = this.signedParams
                        return data
                    },
                },
                template: `
                    <div>
                        <ozon-product-json 
                            v-bind:data='data'
                            v-bind:page='Number(data.PAGE)'
                            v-bind:isStart='isImportStart'
                            v-bind:isFinished='data.FINISHED'
                            v-on:actionStart='actionStart'
                        />
                    </div>
                `
            })
        },

        sendRequest: function (data) {
            this.store.commit('changeData', { isImportStart: true })
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
                    this.store.commit('changeData', { isImportStart: false })
                },
            })
        },
    }
})()