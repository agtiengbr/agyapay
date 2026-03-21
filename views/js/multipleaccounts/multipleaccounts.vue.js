Vue.component('agyapay-multipleaccounts-panel', {
    data() {
        return {
            accounts: [],
            accountForEditing: {},
            status: {
                showGrid: true,
                showForm: false,
                isLoading: false
            },
            errorMessages: [],
            successMessages: []
        }
    },
    mounted() {
        this.loadAccounts();
    },
    template: `
    <div class="main-div">
        <agcliente-notification-alert
            :errors="errorMessages"
            :successes="successMessages"
        ></agcliente-notification-alert>

        <div class="cover-loading" v-if="status.isLoading">
            <agcliente-loading :size="50"></agcliente-loading>
        </div>

        <agpanel>
            <template v-slot:heading>
            Contas ({{ accounts.length }})

                <div class="text-right">
                    <agdropdown :position="'right'">
                        <template v-slot:text><i class="icon-cogs"></i></template>
                        <template v-slot:actions>
                            <div class="dropdown-item" @click="displayForm">Adicionar Conta</div>
                        </template>
                    </agdropdown>
                </div>
            </template>
            
            <agyapay-multipleaccounts-form :account="accountForEditing" @cancel="cancel" @saved='saved' @error='error' v-if="status.showForm"></agyapay-multipleaccounts-form>
            <agyapay-multipleaccounts-list :accounts="accounts" @edit="edit" @remove="remove" v-if="status.showGrid"></agyapay-multipleaccounts-list>
        </agpanel>

    </div>
    `,
    methods: {
        displayGrid() {
            this.loadAccounts();
            this.accountForEditing = [];
            this.status.showGrid = true;
            this.status.showForm = false;
        },
        displayForm() {
            this.status.showGrid = false;
            this.status.showForm = true;
            this.clearMessages();
        },
        async loadAccounts() {
            this.status.isLoading = true;

            try {
                const currentUrl = window.location.href;
                const apiUrl = `${currentUrl}&api=listAccounts`;
                const response = await axios.get(apiUrl);
                this.accounts = response.data.accounts;
            } catch (error) {
                this.$emit('save', '');
            } finally {
                this.status.isLoading = false;
            }
        },
        async remove(idAccount) {
            this.status.isLoading = true;

            try {
                const currentUrl = window.location.href;
                const apiUrl = `${currentUrl}&api=deleteAccount`;

                const response = await axios.post(apiUrl, {account: {id: idAccount}});

                if (response && response.data.success) {
                    this.addSuccessMessage('Conta removida com sucesso.');
                } else {
                    this.addErrorMessage('Ocorreu um erro inesperado, tente novamente mais tarde.');
                }
            } catch (error) {
                this.addErrorMessage('Ocorreu um erro inesperado, tente novamente mais tarde.');
            } finally {
                this.status.isLoading = false;
            }

            this.loadAccounts();
        },
        cancel() {
            this.accountForEditing = [];
            this.displayGrid();
        },
        saved() {
            this.displayGrid();
            this.addSuccessMessage('Conta salva com sucesso.');
        },
        error(error) {
            this.displayGrid();
            this.addErrorMessage(error);
        },
        edit(account) {
            this.accountForEditing = account;
            this.displayForm();
        },
        addErrorMessage(message) {
			this.clearMessages();
			this.errorMessages.push(message);
		},
		addSuccessMessage(message) {
			this.clearMessages();
			this.successMessages.push(message);
		},
		clearMessages() {
			this.errorMessages = [];
			this.successMessages = [];
		}
	}
});
