/* {namespace name=backend/category/main} */
//{block name="backend/category/controller/settings"}
Ext.define('Shopware.apps.Category.controller.boxalino.Narrative', {
    /**
     * Extend from the standard ExtJS 4 controller
     * @string
     */
    override: 'Shopware.apps.Category.controller.Settings',

    /**
     * Enables the form which is disabled by default
     *
     * @return void
     */
    enableForm : function() {
        var me   = this, form = me.getSettingsForm();
        me.callParent(arguments);
        form.narrativeInfo.enable();
    },

    /**
     * Disables the form which is disabled by default
     *
     * @return void
     */
    disableForm : function() {
        var me   = this, form = me.getSettingsForm();
        me.callParent(arguments);
        form.narrativeInfo.disable();
    }
});
//{/block}

