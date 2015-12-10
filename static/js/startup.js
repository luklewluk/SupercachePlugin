pimcore.registerNS("pimcore.plugin.supercache");

pimcore.plugin.supercache = Class.create(pimcore.plugin.admin, {
    getClassName: function() {
        return "pimcore.plugin.supercache";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },
 
    pimcoreReady: function (params,broker){
        // alert("Supercache Ready!");
    }
});

var supercachePlugin = new pimcore.plugin.supercache();

