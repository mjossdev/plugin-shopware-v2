//{block name="backend/category/view/tabs/settings" append}
Ext.define('Shopware.apps.Category.view.main.boxalino.Narrative', {
    override: 'Shopware.apps.Category.view.category.tabs.Settings',

    narrativeInfo : null,

    /**
     * Creates all fields for the form
     *
     * @return array of form elements
     */
    getItems:function ()
    {
        var me = this;
        var items = me.callParent(arguments);
        me.narrativeInfo = me.getNarrativeInfo();
        items.push(me.narrativeInfo);

        return items;
    },

    getNarrativeInfo : function()
    {
        var me = this;
        return Ext.create('Ext.form.FieldSet',{
            title: "Boxalino Narrative",
            anchor: '100%',
            defaults : me.defaults,
            disabled : true,
            items : [{
                xtype : 'textfield',
                fieldLabel  : "Main Choice",
                name:'attribute[narrative_choice]',
            },{
                xtype : 'textareafield',
                fieldLabel  :"Additional Choices",
                name:'attribute[narrative_additional_choice]',
            }]
        });
    }
});
//{/block}
