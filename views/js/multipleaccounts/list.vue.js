Vue.component('agyapay-multipleaccounts-list', {
    props: {
        accounts: Array
    },
    template: `
        <agtable
            :columns-definition="columnsDefinition"
            :columns-data="accountList"
            :display-filter="false"
            :pagination="pagination"
        ></agtable>
    `,
    computed: {
        columnsDefinition() {
            return [
                {
                    title: 'ID',
                    name: 'id',
                    dataType: 'number'
                },
                {
                    title: 'Nome',
                    name: 'name',
                },
                {
                    name: 'actions'
                }
            ];
        },
        pagination() {
            return {
                pageNumber: 1,
                pageSize: 50,
                totalPages: Math.ceil(this.accounts.length / 50)
            }
        },
        accountList() {
            return this.accounts.map(account => ({
                ...account,
                actions: {
                    type: 'component',
                    component: 'agyapay-multipleaccounts-row-actions',
                    props: {
                        account
                    },
                    listeners: {
                        edit: this.edit,
                        remove: this.remove
                    }
                }
            }));
        },
    },
    methods: {
        edit(account) {
            this.$emit('edit', account);
        },
        remove(account) {
            this.$emit('remove', account);
        }
    }
});
