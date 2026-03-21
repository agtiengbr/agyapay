Vue.component('agyapay-multipleaccounts-form', {
    props: {
        account: {
            default: () => ({
                name: '',
                account_token: '',
                account_token_sandbox: '',
                carriers: []
            })
        }
    },
    data() {
        return {
            account_data: this.account,
            carriers: [],
            existingCarrierIds: [],
            selectAllChecked: false,
            group_data: {
                carriers: {
                    ids: []
                }
            },
            status: {
                isEditing: false,
                isSaving: false,
                isLoading: false
            },
            errorMessages: [],
            successMessages: []
        };
    },
    template:
      `
        <div class="form-horizontal">

            <div class='form-group row'>
                <label class="col-lg-3 control-label">Nome</label>
                <div class="col-lg-3">
                    <input class="form-control" type="text" v-model="account_data.name"/>
                </div>
            </div>

            <div class='form-group row'>
                <label class="col-lg-3 control-label">Token da Conta</label>
                <div class="col-lg-3">
                    <input class="form-control" type="text" v-model="account_data.account_token"/>
                </div>
            </div>

            <div class='form-group row'>
                <label class="col-lg-3 control-label">Token Sandbox da Conta</label>
                <div class="col-lg-3">
                    <input class="form-control" type="text" v-model="account_data.account_token_sandbox"/>
                </div>
            </div>

            <div class='form-group row'>
                <label class="col-lg-3 control-label">URL de Notificação</label>
                <div class="col-lg-3">
                    <input class="form-control" type="text" v-model="account_data.notification_url" placeholder="URL de Notificação"/>
                </div>
            </div>

            <div class='form-group row'>
                <label class="col-lg-3 control-label">Transportadoras</label>
                <div class="col-lg-5">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="selectAllChecked" @change="toggleSelectAll">
                        Selecionar todas as transportadoras
                    </label>
                    <hr>
                    <div>
                        <div>
                            <label for="0">
                                <input type="checkbox" :id="0" :value="{id: null, name: 'Produtos Virtuais'}" :checked="isCarrierChecked({id: null, name: 'Produtos Virtuais'})" @click.stop="toggleCarrier({id: null, name: 'Produtos Virtuais'})">
                                Produtos Virtuais<br>
                            </label>
                        </div>
                        <div v-for="carrier in carriers">
                            <label :for="carrier.id">
                                <input type="checkbox" :id="carrier.id" :value="{id: carrier.id, name: carrier.name}" :checked="isCarrierChecked({id: carrier.id, name: carrier.name})" @click.stop="toggleCarrier({id: carrier.id, name: carrier.name})">
                                {{ carrier.id }} - {{ carrier.name }}<br>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="form-group row buttons">
                <div class="btn btn-primary" @click="save" :disabled="status.isLoading">Salvar</div>
                <div class="btn btn-danger" @click="cancel">Voltar</div>
                <div class="btn" v-if="status.isLoading">
                    <agcliente-loading :size="25"></agcliente-loading>
                </div>
            </div>

            <agcliente-notification-alert
                :errors="errorMessages"
                :successes="successMessages"
            ></agcliente-notification-alert>

        </div>
      `,
      mounted() {
        this.loadCarriers();
    
        if(this.account_data.id) {
            this.group_data.carriers.ids = [];
            this.existingCarrierIds = [];
    
            this.account_data.carriers.forEach(carrier => {
                if (carrier.carrier) {
                    this.group_data.carriers.ids.push({id: carrier.carrier.id, name: carrier.carrier.name});
                    this.existingCarrierIds[carrier.carrier.id] = true;
                } else {
                    this.group_data.carriers.ids.push({id: null, name: 'Produtos Virtuais'});
                    this.existingCarrierIds[null] = true;
                }
            });
        }
    },    
    methods: {
        toggleSelectAll() {
            if (this.selectAllChecked) {
                this.selectAll();
            } else {
                this.group_data.carriers.ids = [];
            }
        },
        selectAll() {
            this.group_data.carriers.ids = this.carriers.map(carrier => {
                return {id: carrier.id, name: carrier.name};
            });

            this.group_data.carriers.ids.push({id: null, name: 'Produtos Virtuais'});
        },
        isCarrierChecked(carrier) {
            return this.group_data.carriers.ids.some(
                c => c.id === carrier.id && c.name === carrier.name
            );
        },
        toggleCarrier(carrier) {
            const index = this.group_data.carriers.ids.findIndex(
                c => c.id === carrier.id && c.name === carrier.name
            );
            if (index > -1) {
                this.group_data.carriers.ids.splice(index, 1);
            } else {
                this.group_data.carriers.ids.push(carrier);
            }
        },
        clearMessages() {
            this.errorMessages = [];
            this.successMessages = [];
        },
        async loadCarriers() {
            this.status.isLoading = true;

            try {
                const currentUrl = window.location.href;
                const apiUrl = `${currentUrl}&api=listCarriers`;
                const response = await axios.get(apiUrl);
                this.carriers = response.data.carriers;
            } catch (error) {
                this.errorMessages.push('Ocorreu um erro inesperado, tente novamente mais tarde.');
            } finally {
                this.status.isLoading = false;
            }
        },
        async save() {
            let idAccount = await this.saveAccount();
            let carriersRegistred = await this.registerCarriersToAccount(idAccount);
            let carriersRemoved = true;

            if(this.account_data.id) {
                carriersRemoved = await this.removeCarriersFromAccount(this.account_data.id);
            }

            if(idAccount && carriersRegistred && carriersRemoved) {
                this.$emit('saved');
            }
        },
        async registerCarriersToAccount(idAccount) {
            this.status.isLoading = true;
            this.clearMessages();

            const currentUrl = window.location.href;
            const apiUrl = `${currentUrl}&api=addCarrierToAccount`;

            let allSuccessful = true;
            let response = false;
            let error = 'Ocorreu um erro inesperado, tente novamente mais tarde.';

            for (let i = 0; i < this.group_data.carriers.ids.length; i++) {
                if (!this.existingCarrierIds[this.group_data.carriers.ids[i].id]) {
                    try {
                        response = await axios.post(apiUrl, 
                            { 
                                account: {
                                    id: idAccount
                                }, 
                                carrier: {
                                    id: this.group_data.carriers.ids[i].id
                                }
                            }
                        );

                        if(!response.data.success) {
                            if(response.data.error) {
                                error = response.data.error;
                            } 
                            this.$emit('error', error);
                            allSuccessful = false;
                            break;
                        }
                    } catch (error) {
                        allSuccessful = false;
                        break;
                    }
                }
            }

            this.status.isLoading = false;
            return allSuccessful;
        },
        async saveAccount() {
            this.status.isLoading = true;
            this.clearMessages();

            const currentUrl = window.location.href;
            const apiUrl = `${currentUrl}&api=saveAccount`;
            let error = 'Ocorreu um erro inesperado, tente novamente mais tarde.';

            try {
                const response = await axios.post(apiUrl,
                    {
                        account: {
                            id: this.account_data.id, 
                            name: this.account_data.name, 
                            account_token: this.account_data.account_token, 
                            account_token_sandbox: this.account_data.account_token_sandbox,
                            notificationUrl: this.account_data.notification_url
                        }
                    }
                );

                if(response.data.success) {
                    return response.data.account.id;
                } else {
                    if(response.data.error) {
                        error = response.data.error;
                    } 
                    this.$emit('error', error);
                    return false
                }
            } catch (error) {
                return false;
            } finally {
                this.status.isLoading = false;
            }
        },
        async removeCarriersFromAccount(idAccount) {
            this.status.isLoading = true;
            this.clearMessages();
        
            const currentUrl = window.location.href;
            const apiUrl = `${currentUrl}&api=removeCarrierFromAccount`;
        
            let selectedCarrierIds = {};
            this.group_data.carriers.ids.forEach(carrier => {
                if(carrier.id) {
                    selectedCarrierIds[carrier.id] = true;
                } else if (carrier.id == null) {
                    selectedCarrierIds[0] = true;
                }
            });
        
            let allSuccessful = true;
            let response = false;
            let idCarrier;
            let error = 'Ocorreu um erro inesperado, tente novamente mais tarde.';
        
            for (let i = 0; i < this.account_data.carriers.length; i++) {
                // Se a transportadora existente não estiver na lista de transportadoras selecionadas, remova-a
                
                if(this.account_data.carriers[i].carrier) {
                    idCarrier = this.account_data.carriers[i].carrier.id;
                } else if (this.account_data.carriers[i].id) {
                    idCarrier = 0;
                }

                if (!selectedCarrierIds[idCarrier]) {
                    try {
                        response = await axios.post(apiUrl, 
                            { 
                                account: {
                                    id: idAccount
                                }, 
                                carrier: {
                                    id: idCarrier == 0 ? null : idCarrier
                                }
                            }
                        );
        
                        if(!response.data.success) {
                            if(response.data.error) {
                                error = response.data.error;
                            } 
                            this.$emit('error', error);

                            allSuccessful = false;
                            break;
                        }
                    } catch (error) {
                        allSuccessful = false;
                        break;
                    }
                }
            }
        
            this.status.isLoading = false;
            return allSuccessful;
        },  
        cancel() {
            this.$emit('cancel');
        }
    }
});
