window.catTemplate = {
    clearText: function(f)
    {
        var cfg = mw.config.get('catTpl');
        if (f.defaultValue == f.value)
        {
            f.value = cfg.deftitle;
            if (cfg.deftitlepos !== false)
            {
                f.selectionStart = cfg.deftitlepos;
                f.selectionEnd = cfg.deftitlepos;
            }
        }
    },
    addText: function(f)
    {
        var cfg = mw.config.get('catTpl');
        if (f.value == cfg.deftitle || f.value == "")
        {
            f.value = f.defaultValue;
        }
    },
    checkName: function()
    {
        var cfg = mw.config.get('catTpl');
        var inp = document.getElementById('createboxInput');
        var l = 0;
        var txt = cfg.text.replace(/__FULLPAGENAME__/g, inp.value);
        var ns_file = new RegExp(cfg.ns_file), sf_form;
        if (ns_file.exec(inp.value))
        {
            // File upload
            document.createbox.method = 'POST';
            document.getElementById('createbox_action').value = 'edit';
            document.createbox.wpDestFile.value = inp.value.substr(l);
            document.createbox.wpUploadDescription.value = txt;
            document.createbox.wpTextbox1.value = "";
            document.createbox.title.value = 'Special:Upload';
        }
        else if (sf_form = mw.config.get('sfDefaultForm'))
        {
            // Create page with semantic form
            document.createbox.method = 'GET';
            document.getElementById('createbox_action').value = '';
            document.createbox.wpTextbox1.value = "";
            document.createbox.title.value = 'Special:FormEdit/'+sf_form+'/'+inp.value;
        }
        else
        {
            // Normal page
            document.createbox.method = 'POST';
            document.getElementById('createbox_action').value = 'edit';
            document.createbox.wpUploadDescription.value = "";
            document.createbox.wpTextbox1.value = txt;
            document.createbox.title.value = inp.value;
        }
        return inp.value != inp.defaultValue || confirm(mw.msg('addcategorytemplate-confirm', document.createbox.createboxInput.value));
    }
};
