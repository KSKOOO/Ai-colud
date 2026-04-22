


const UserPermissions = {
    data: null,
    loaded: false,
    
    
    load: function(callback) {
        const self = this;
        
        $.get('api/permission_handler.php?action=getMyPermissions', function(response) {
            if (response.success) {
                self.data = response.data;
                self.loaded = true;
                

                sessionStorage.setItem('user_permissions', JSON.stringify(self.data));
            }
            
            if (callback) {
                callback(response.success ? null : response.error, self.data);
            }
        }).fail(function() {
            if (callback) {
                callback('加载权限失败', null);
            }
        });
    },
    
    
    getCached: function() {
        if (this.data) {
            return this.data;
        }
        
        const cached = sessionStorage.getItem('user_permissions');
        if (cached) {
            try {
                this.data = JSON.parse(cached);
                this.loaded = true;
                return this.data;
            } catch (e) {
                return null;
            }
        }
        
        return null;
    },
    
    
    hasModule: function(module) {
        const perms = this.getCached();
        if (!perms) {
            return true;
        }
        

        if (perms.is_admin) {
            return true;
        }
        

        if (perms.modules && perms.modules.hasOwnProperty(module)) {
            return perms.modules[module];
        }
        

        return true;
    },
    
    
    hasModel: function(providerId, modelId) {
        const perms = this.getCached();
        if (!perms) {
            return true;
        }
        
        if (perms.is_admin) {
            return true;
        }
        
        const key = providerId + ':' + modelId;
        

        if (perms.models && perms.models.hasOwnProperty(key)) {
            return perms.models[key].allowed;
        }
        

        const wildcardKey = providerId + ':*';
        if (perms.models && perms.models.hasOwnProperty(wildcardKey)) {
            return perms.models[wildcardKey].allowed;
        }
        
        return true;
    },
    
    
    hasTraining: function(modelName) {
        const perms = this.getCached();
        if (!perms) {
            return true;
        }
        
        if (perms.is_admin) {
            return true;
        }
        
        if (modelName && perms.training) {

            const specific = perms.training.find(t => t.model_name === modelName);
            if (specific) {
                return specific.allowed == 1;
            }
            

            const wildcard = perms.training.find(t => t.model_name === '*');
            if (wildcard) {
                return wildcard.allowed == 1;
            }
        }
        
        return true;
    },
    
    
    isAdmin: function() {
        const perms = this.getCached();
        return perms ? perms.is_admin : false;
    },
    
    
    apply: function() {
        const self = this;
        

        $('[data-require-module]').each(function() {
            const module = $(this).data('require-module');
            if (!self.hasModule(module)) {
                $(this).remove();
            }
        });
        

        $('[data-require-model]').each(function() {
            const modelInfo = $(this).data('require-model');
            if (modelInfo) {
                const parts = modelInfo.split(':');
                if (parts.length === 2) {
                    if (!self.hasModel(parts[0], parts[1])) {
                        $(this).prop('disabled', true).addClass('disabled');
                    }
                }
            }
        });
        

        $('[data-require-training]').each(function() {
            const modelName = $(this).data('require-training');
            if (!self.hasTraining(modelName)) {
                $(this).prop('disabled', true).addClass('disabled');
            }
        });
    },
    
    
    clear: function() {
        this.data = null;
        this.loaded = false;
        sessionStorage.removeItem('user_permissions');
    }
};


$(document).ready(function() {

    if ($('[data-require-module], [data-require-model], [data-require-training]').length > 0) {
        UserPermissions.load(function(err) {
            if (!err) {
                UserPermissions.apply();
            }
        });
    }
});


window.UserPermissions = UserPermissions;
