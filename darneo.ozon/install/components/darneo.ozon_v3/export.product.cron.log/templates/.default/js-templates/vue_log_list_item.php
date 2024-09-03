<script>
BX.BitrixVue.component('ozon-log-list-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
    },
    template: `
        <tr>
        <td v-html='item.DATE_CREATED'></td>
        <td v-html='item.DATE_FINISHED'></td>
        </tr>
    `,
})
</script>