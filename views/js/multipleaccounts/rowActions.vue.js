Vue.component('agyapay-multipleaccounts-row-actions', {
    props: {
        account: Object
    },
    template: `
        <div class="actions">
            <button type="button" class="btn btn-default" @click="edit" title="Editar">
                <i class="icon icon-pencil"></i>
            </button>
            <button type="button" class="btn btn-danger" @click="remove" title="Excluir">
                <i class="icon icon-times"></i>
            </button>
        </div>
    `,
    methods: {
        edit() {
            this.$emit('edit', this.account);
        },
        remove() {
            if (window.confirm(`Deseja realmente excluir a conta de ID ${this.account.id}? Essa operação é irreversível.`)) {
                this.$emit('remove', this.account.id);
            }
        }
    }
});
