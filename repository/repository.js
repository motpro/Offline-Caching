// $Id$
///////////////////////////////////////////////////////////////////////////
//                                                                       //
//       Don't modify this file unless you know how it works             //
//                                                                       //
///////////////////////////////////////////////////////////////////////////
/**
 * repository_client is a javascript class, it contains several static 
 * methods you can call it directly without creating an instance.
 * If you are going to create a file picker, you need create an instance
 * repo = new repository_client();
 
 */

var repository_listing = {};
var active_instances = {};
var file_extensions = {};
// repository_client has static functions
var repository_client = (function(){
    // private static field
    var version = '2.0';
    var PANEL_BODY_PADDING = (10*2);
    // private static methods
    function help() {
        alert(version);
    }
    // a hack to fix ie6 bug
    function ie6_fix_width(id, width) {
        if(YAHOO.env.ua.ie == 6){
            var fp_title = document.getElementById('file-picker-'+id);
            fp_title.style.width = width;
        }
    }
    function $() {
        // public methods of filepicker instance
        this.create_filepicker = function(client_id) {
            var IE_QUIRKS = (YAHOO.env.ua.ie && document.compatMode == "BackCompat");
            var IE_SYNC = (YAHOO.env.ua.ie == 6 || (YAHOO.env.ua.ie == 7 && IE_QUIRKS));
            var btn_listing = {label: fp_lang.listview, value: 'l',
                onclick: {fn: repository_client.view_as_list, obj:client_id}};
            var btn_icons = {label: fp_lang.thumbview, value: 't',
                onclick: {fn: repository_client.view_as_icons, obj:client_id}};
            document.body.className += ' yui-skin-sam';
            var el = document.createElement('DIV');
            el.id = 'file-picker-'+client_id;
            el.className = 'file-picker';
            this.client_id = client_id;
            document.body.appendChild(el);
            this.filepicker = new YAHOO.widget.Panel('file-picker-' + client_id, {
                draggable: true,
                close: true,
                underlay: 'none',
                zindex: 666666,
                xy: [50, YAHOO.util.Dom.getDocumentScrollTop()+20]
            });
            var layout = '';
            this.filepicker.client_id = client_id;
            this.filepicker.setHeader(fp_lang['title']);
            this.filepicker.setBody('<div id="layout-'+client_id+'"></div>');
            this.filepicker.beforeRenderEvent.subscribe(function() {
                YAHOO.util.Event.onAvailable('layout-'+client_id, function() {
                    layout = new YAHOO.widget.Layout('layout-'+client_id, {
                        height: 480, width: 700,
                        units: [
                        {position: 'top', height: 32, resize: false,
                        body:'<div class="yui-buttongroup fp-viewbar" id="repo-viewbar-'+client_id+'"></div><div class="fp-searchbar" id="search-div-'+client_id+'"></div>', gutter: '2'},
                        {position: 'left', width: 200, resize: true, scroll:true,
                        body:'<ul class="fp-list" id="repo-list-'+client_id+'"></ul>', gutter: '0 5 0 2', minWidth: 150, maxWidth: 300 },
                        {position: 'center', body: '<div class="fp-panel" id="panel-'+client_id+'"></div>',
                        scroll: true, gutter: '0 2 0 0' }
                        ]
                    });
                    layout.render();
                });
            });
            var resize = new YAHOO.util.Resize('file-picker-'+client_id, {
                handles: ['br'],
                autoRatio: true,
                status: true,
                minWidth: 680,
                minHeight: 400
            });
            ie6_fix_width(client_id, '680px');
            resize.on('resize', function(args) {
                var panelHeight = args.height;
                var headerHeight = this.header.offsetHeight; // Content + Padding + Border
                var bodyHeight = (panelHeight - headerHeight);
                var bodyContentHeight = (IE_QUIRKS) ? bodyHeight : bodyHeight - PANEL_BODY_PADDING;
                YAHOO.util.Dom.setStyle(this.body, 'height', bodyContentHeight + 'px');
                ie6_fix_width(this.client_id, '680px');
                if (IE_SYNC) {
                    this.sizeUnderlay();
                    this.syncIframe();
                }
                layout.set('height', bodyContentHeight);
                layout.set('width', (args.width - PANEL_BODY_PADDING));
                layout.resize();
            }, this.filepicker, true);
            repository_client.fp[client_id].viewbar = new YAHOO.widget.ButtonGroup({
                id: 'btngroup-'+client_id,
                name: 'buttons',
                disabled: true,
                container: 'repo-viewbar-'+client_id
            });
            repository_client.fp[client_id].viewbar.addButtons([btn_icons, btn_listing]);
            this.print_listing();
            this.filepicker.render();
        }
        this.print_listing = function() {
            var container = new YAHOO.util.Element('repo-list-'+this.client_id);
            container.set('innerHTML', '');
            container.on('contentReady', function() {
                for(var i in repository_listing[this.client_id]) {
                    var repo = repository_listing[this.client_id][i];
                    var support = false;
                    if(this.env=='editor' && this.accepted_types != '*'){
                        if(repo.supported_types!='*'){
                            for (var j in repo.supported_types){
                                if(mdl_in_array(repo.supported_types[j], this.accepted_types)){
                                    support = true;
                                }
                            }
                        }
                    }else{
                        support = true;
                    }
                    if(repo.supported_types == '*' || support){
                        var li = document.createElement('li');
                        li.id = 'repo-'+this.client_id+'-'+repo.id;
                        var icon = document.createElement('img');
                        icon.src = repo.icon;
                        icon.width = '16';
                        icon.height = '16';
                        var link = document.createElement('a');
                        link.href = '###';
                        link.id = 'repo-call-'+this.client_id+'-'+repo.id;
                        link.appendChild(icon);
                        link.className = 'fp-repo-name';
                        link.innerHTML += ' '+repo.name;
                        link.onclick = function() {
                            var re = new RegExp("repo-call-(\\w+)-(\\d+)", "i");
                            var result = this.id.match(re);
                            var client_id = result[1];
                            var repo_id = result[2];
                            // high light currect selected repository
                            for(var cc in repository_listing[client_id]){
                                var tmp_id = 'repo-call-'+client_id+'-'+ cc;
                                var el = document.getElementById(tmp_id);
                                if(el){
                                    el.style.background = 'transparent';
                                }
                            }
                            this.style.background = '#CCC';
                            repository_client.loading(client_id, 'load');
                            repository_client.req(client_id, repo_id, '');
                        }
                        li.appendChild(link);
                        container.appendChild(li);
                        repo = null;
                    }
                }
            }, this, true);
        }
        this.show = function(){
            this.print_listing();
            var panel = new YAHOO.util.Element('panel-'+this.filepicker.client_id);
            panel.get('element').innerHTML = '';
            this.filepicker.show();
        }
        this.hide = function(){
            this.filepicker.hide();
        }
    }
    // all filepicker instances
    $.fp = {};
    return $;
})();
// public static method
// may be called outside yui
repository_client.req = function(client_id, id, path, page) {
    this.fp[client_id].viewbar.set('disabled', false);
    var r = repository_client.fp[client_id];
    var params = [];
    params['p'] = path;
    params['env']=r.env;
    params['sesskey']=moodle_cfg.sesskey;
    params['ctx_id']=fp_config.contextid;
    params['client_id'] = client_id;
    params['repo_id']=id;
    if (!!page) { // convert page to boolean value
        params['page']=page;
    }
    params['accepted_types'] = r.accepted_types;
    var trans = YAHOO.util.Connect.asyncRequest('POST', moodle_cfg.wwwroot+'/repository/ws.php?action=list', this.req_cb, this.postdata(params));
}

repository_client.req_cb = {
    success: function(o){
         var data = repository_client.parse_json(o.responseText, 'req_cb');    
         var repo = repository_client.fp[data.client_id];
         var panel = new YAHOO.util.Element('panel-'+data.client_id);
         if(data && data.e) {
             panel.get('element').innerHTML = data.e;
             return;
         }
         // save data
         repo.fs = data;
         if(!data) {
             return;
         }else if(data.msg){
             repository_client.print_msg(data.msg);
         }else if(data.iframe) {
             repository_client.view_iframe(data.client_id);
         }else if(data.login) {
             repository_client.print_login(data.client_id, data);
         }else if(data.list) {
             if(repo.view_status) {
                 repository_client.view_as_list(data.client_id, data.list);
             } else {
                 repository_client.view_as_icons(data.client_id, data.list);
             }
         }
     }
}
repository_client.view_iframe = function(client_id) {
    var fs = repository_client.fp[client_id].fs;
    var panel = new YAHOO.util.Element('panel-'+client_id);
    panel.get('element').innerHTML = "<iframe frameborder=\"0\" width=\"98%\" height=\"400px\" src=\""+fs.iframe+"\" />";
}
repository_client.req_search_results = function(client_id, id, path, page) {
    this.fp[client_id].viewbar.set('disabled', false);
    var r = repository_client.fp[client_id];
    var params = [];
    params['p'] = path;
    params['env']=r.env;
    params['sesskey']=moodle_cfg.sesskey;
    params['ctx_id']=fp_config.contextid;
    params['client_id'] = client_id;
    params['search_paging']='true';
    params['repo_id']=id;
    if (!!page) { // convert page to boolean value
        params['page']=page;
    }
    params['accepted_types'] = r.accepted_types;
    var trans = YAHOO.util.Connect.asyncRequest('POST', moodle_cfg.wwwroot+'/repository/ws.php?action=search', this.req_cb, this.postdata(params));
}

repository_client.print_login = function(id, data) {
    var login = data.login;
    var panel = new YAHOO.util.Element('panel-'+id);
    var str = '<div class="fp-login-form">';
    var has_pop = false;
    this.fp[id].login = login;
    for(var k in login) {
        if(login[k].type=='popup') {
            str += '<p class="fp-popup">'+fp_lang.popup+'</p>';
            str += '<p class="fp-popup"><button onclick="repository_client.popup(\''+id+'\', \''+login[k].url+'\')">'+fp_lang.login+'</button>';
            str += '</p>';
            has_pop = true;
        }else if(login[k].type=='textarea') {
            str += '<p><textarea id="'+login[k].id+'" name="'+login[k].name+'"></textarea></p>';
        }else{
            str += '<p>';
            var label_id = '';
            var field_id = '';
            var field_value = '';
            if(login[k].id) {
                label_id = ' for="'+login[k].id+'"';
                field_id = ' id="'+login[k].id+'"';
            }
            if (login[k].label) {
                str += '<label'+label_id+'>'+login[k].label+'</label>&nbsp;';
            }
            if(login[k].value) {
                field_value = ' value="'+login[k].value+'"';
            }
            str += '<input type="'+login[k].type+'"'+' name="'+login[k].name+'"'+field_id+field_value+' />';
            str += '</p>';
        }
    }
    var btn_label = login['login_btn_label']?login['login_btn_label']:fp_lang.submit;
    if (data['login_search_form']) {
        str += '<p><input type="button" onclick="repository_client.search(\''+id+'\', \''+data.repo_id+'\')" value="'+btn_label+'" /></p>';
    } else {
        if(!has_pop) {
            str += '<p><input type="button" onclick="repository_client.login(\''+id+'\', \''+data.repo_id+'\')" value="'+btn_label+'" /></p>';
        }
    }
    str += '</div>';
    panel.get('element').innerHTML = str;
}
repository_client.login = function(id, repo_id) {
    var params = [];
    var data = this.fp[id].login;
    for (var k in data) {
        if(data[k].type!='popup') {
            var el = document.getElementsByName(data[k].name)[0];
            params[data[k].name] = '';
            if(el.type == 'checkbox') {
                params[data[k].name] = el.checked;
            } else {
                params[data[k].name] = el.value;
            }
        }
    }
    params['env'] = this.fp[id].env;
    params['repo_id'] = repo_id;
    params['client_id'] = id;
    params['ctx_id'] = fp_config.contextid;
    params['sesskey'] = moodle_cfg.sesskey;
    params['accepted_types'] = this.fp[id].accepted_types;
    this.loading(id, 'load');
    var trans = YAHOO.util.Connect.asyncRequest('POST',
            moodle_cfg.wwwroot+'/repository/ws.php?action=sign', this.req_cb, this.postdata(params));
}
repository_client.search = function(id, repo_id) {
    var params = [];
    var data = this.fp[id].login;
    for (var k in data) {
        if(data[k].type!='popup') {
            var el = document.getElementsByName(data[k].name)[0];
            params[data[k].name] = '';
            if(el.type == 'checkbox') {
                params[data[k].name] = el.checked;
            } else {
                params[data[k].name] = el.value;
            }
        }
    }
    params['env'] = this.fp[id].env;
    params['repo_id'] = repo_id;
    params['client_id'] = id;
    params['ctx_id'] = fp_config.contextid;
    params['sesskey'] = moodle_cfg.sesskey;
    params['accepted_types'] = this.fp[id].accepted_types;
    this.loading(id, 'load');
    var trans = YAHOO.util.Connect.asyncRequest('POST',
            moodle_cfg.wwwroot+'/repository/ws.php?action=search', this.req_cb, this.postdata(params));
}
repository_client.loading = function(id, type, name) {
    var panel = new YAHOO.util.Element('panel-'+id);
    panel.get('element').innerHTML = '';
    var content = document.createElement('div');
    content.style.textAlign='center';
    var para = document.createElement('P');
    var img = document.createElement('IMG');
    if(type=='load') {
        img.src = moodle_cfg.pixpath+'/i/loading.gif';
        para.innerHTML = fp_lang.loading;
    }else{
        img.src = moodle_cfg.pixpath+'/i/progressbar.gif';
        para.innerHTML = fp_lang.copying+' <strong>'+name+'</strong>';
    }
    content.appendChild(para);
    content.appendChild(img);
    panel.get('element').appendChild(content);
}
repository_client.view_as_list = function(client_id, data) {
    if (typeof client_id == 'object') {
        // click button
        client_id = data;
        list = repository_client.fp[client_id].fs.list;
    } else if(!data) {
        // from viewfiles
        list = repository_client.fp[client_id].fs.list;
    }else{
        // from callback 
        list = data;
    }
    var panel = new YAHOO.util.Element('panel-'+client_id);
    var fp = repository_client.fp[client_id];
    fp.view_status = 1;
    fp.viewbar.check(1);
    repository_client.print_header(client_id);
    panel.get('element').innerHTML += '<div id="treediv-'+client_id+'"></div>';
    var tree = new YAHOO.widget.TreeView('treediv-'+client_id);
    tree.dynload = function (node, fnLoadComplete) {
        var callback = {
            success: function(o) {
                 var json = repository_client.parse_json(o.responseText, 'dynload');    
                 for(k in json.list) {
                     repository_client.buildtree(json.client_id, json.list[k], node);
                 }
                 o.argument.fnLoadComplete();
            },
            failure:function(oResponse) {
                alert(fp_lang.error+' - |dynload| -');
                oResponse.argument.fnLoadComplete();
            },
            argument:{"node":node, "fnLoadComplete": fnLoadComplete}
        }
        var fp = repository_client.fp[node.client_id];
        var params = [];
        params['p']=node.path;
        params['env']=fp.env;
        params['sesskey']=moodle_cfg.sesskey;
        params['ctx_id']=fp_config.contextid;
        params['repo_id']=fp.fs.repo_id;
        params['client_id']=node.client_id;
        params['accepted_types']=fp.accepted_types;
        var trans = YAHOO.util.Connect.asyncRequest('POST',
                moodle_cfg.wwwroot+'/repository/ws.php?action=list',callback,repository_client.postdata(params));
    }
    tree.dynload.client_id = client_id;
    if(fp.fs.dynload) {
        tree.setDynamicLoad(tree.dynload, 1);
    } else {
    }
    for(k in list) {
        repository_client.buildtree(client_id, list[k], tree.getRoot());
    }
    tree.draw();
    tree.subscribe('clickEvent', function(e){
        if(e.node.isLeaf){
            repository_client.select_file(e.node.data.filename, e.node.data.value, e.node.data.icon, client_id, e.node.repo_id);
        }
    });
    repository_client.print_footer(client_id);
}
repository_client.buildtree = function(client_id, node, level) {
    var fs = repository_client.fp[client_id].fs;
    if(node.children) {
        node.title = '<i><u>'+node.title+'</u></i>';
    }
    var info = {
        label:node.title,
        title:fp_lang.date+' '+node.date+fp_lang.size+' '+node.size,
        filename:node.title,
        value:node.source,
        icon:node.thumbnail,
        path:node.path
    };
    var tmpNode = new YAHOO.widget.TextNode(info, level, false);
    var tooltip = new YAHOO.widget.Tooltip(tmpNode.labelElId, {
        context:tmpNode.labelElId, text:info.title});
    if(node.repo_id) {
        tmpNode.repo_id=node.repo_id;
    }else{
        tmpNode.repo_id=fs.repo_id;
    }
    if(node.children) {
        if(node.expanded) {
            tmpNode.expand();
        }
        tmpNode.isLeaf = false;
        tmpNode.client_id = client_id;
        if (node.path) {
            tmpNode.path = node.path;
        } else {
            tmpNode.path = '';
        }
        for(var c in node.children) {
            this.buildtree(client_id, node.children[c], tmpNode);
        }
    } else {
        tmpNode.isLeaf = true;
    }
}
repository_client.select_file = function(oldname, url, icon, client_id, repo_id) {
    var thumbnail = document.getElementById('fp-grid-panel-'+client_id);
    if(thumbnail){
        thumbnail.style.display = 'none';
    }
    var header = document.getElementById('fp-header-'+client_id);
    header.style.display = 'none';
    var footer = document.getElementById('fp-footer-'+client_id);
    footer.style.display = 'none';
    var panel = new YAHOO.util.Element('panel-'+client_id);
    var html = '<div class="fp-rename-form">';
    html += '<p><img src="'+icon+'" /></p>';
    html += '<p><label for="newname-'+client_id+'">'+fp_lang.saveas+'</label>';
    html += '<input type="text" id="newname-'+client_id+'" value="'+oldname+'" /></p>';
    html += '<p><input type="hidden" id="fileurl-'+client_id+'" value="'+url+'" />';
    html += '<input type="button" onclick="repository_client.download(\''+client_id+'\', \''+repo_id+'\')" value="'+fp_lang.downbtn+'" />';
    html += '<input type="button" onclick="repository_client.viewfiles(\''+client_id+'\')" value="'+fp_lang.cancel+'" /></p>';
    html += '</div>';
    panel.get('element').innerHTML += html;
    var tree = document.getElementById('treediv-'+client_id);
    if(tree){
        tree.style.display = 'none';
    }
}
repository_client.paging = function(client_id, id) {
    var str = '';
    this.fp[client_id].view_staus = 0;
    var fs = this.fp[client_id].fs;
    if(fs.pages) {
        str += '<div class="fp-paging" id="paging-'+id+'-"'+client_id+'>';
        if(!fs.search_result){
            var action = 'req';
        } else {
            var action = 'req_search_results';
        }
        str += this.get_page_btn(client_id, action, 1)+'1</a>';

        if (fs.page+2>=fs.pages) {
            var max = fs.pages;
        } else {
            var max = fs.page+2;
        }
        if (fs.page-2 >= 3) {
            str += ' ... ';
            for(var i=fs.page-2; i<max; i++) {
                str += this.get_page_btn(client_id, action, i);
                str += String(i);
                str += '</a> ';
            }
        } else {
            for(var i = 2; i < max; i++) {
                str += this.get_page_btn(client_id, action, i);
                str += String(i);
                str += '</a> ';
            }
        }
        if (max==fs.pages) {
            str += this.get_page_btn(client_id, action, fs.pages)+fs.pages+'</a>';
        } else {
            str += repository_client.get_page_btn(client_id, action, max)+max+'</a>';
            str += ' ... '+repository_client.get_page_btn(client_id, action, fs.pages)+fs.pages+'</a>';
        }
        str += '</div>';
    }
    return str;
}
repository_client.get_page_btn = function(client_id, type, page) {
    var fs = this.fp[client_id].fs;
    var css = '';
    if (page == fs.page) {
        css = 'class="cur_page"';
    }
    str = '<a '+css+' onclick="repository_client.'+type+'(\''+client_id+'\','+fs.repo_id+', '+page+', '+page+')" href="###">';
    return str;
}
repository_client.path = function(client_id) {
    var fs = this.fp[client_id].fs;
    // if this is listing view
    if(this.fp[client_id].view_status == 1) {
        return;
    }
    var panel = new YAHOO.util.Element('panel-'+client_id);
    var p = fs.path;
    if(p && p.length!=0) {
        var oDiv = document.createElement('DIV');
        oDiv.id = "path-"+client_id;
        oDiv.className = "fp-pathbar";
        panel.get('element').appendChild(oDiv);
        for(var i = 0; i < fs.path.length; i++) {
            var link = document.createElement('A');
            link.href = "###";
            link.innerHTML = fs.path[i].name;
            link.id = 'path-'+i+'-el';
            var sep = document.createElement('SPAN');
            sep.innerHTML = '/';
            oDiv.appendChild(link);
            oDiv.appendChild(sep);
            var el = new YAHOO.util.Element(link.id);
            el.id = fs.repo_id;
            el.path = fs.path[i].path;
            el.link_id = link.id;
            el.client_id = client_id;
            el.on('contentReady', function() {
                var path_link = document.getElementById(this.link_id);
                path_link.id = this.id;
                path_link.path = this.path;
                path_link.client_id = this.client_id;
                path_link.onclick = function() {
                    repository_client.req(this.client_id, this.id, this.path);
                }
            });
            link = null;
        }
    }
}
repository_client.print_header = function(client_id) {
    var panel = new YAHOO.util.Element('panel-'+client_id);
    var str = '<div id="fp-header-'+client_id+'">';
    str += '<div class="fp-toolbar" id="repo-tb-'+client_id+'"></div>';
    if(this.fp[client_id].fs.pages < 8){
        str += this.paging(client_id, 'header');
    }
    str += '</div>';
    panel.set('innerHTML', str);
    this.path(client_id);
}
repository_client.view_as_icons = function(client_id, data) {
    var list = null;
    if (typeof client_id == 'object') {
        // click button
        alert('from button');
        client_id = data;
        list = repository_client.fp[client_id].fs.list;
    } else if(!data) {
        // from viewfiles
        list = repository_client.fp[client_id].fs.list;
    }else{
        //
        list = data;
    }
    var fp = repository_client.fp[client_id];
    fp.view_status = 0;
    fp.viewbar.check(0);
    var container = document.getElementById('panel-'+client_id);
    var panel = document.createElement('DIV');
    panel.id = 'fp-grid-panel-'+client_id;
    repository_client.print_header(client_id);
    var count = 0;
    for(k in list) {
        // the container
        var el = document.createElement('div');
        el.className='fp-grid';
        // the file name
        var title = document.createElement('div');
        title.id = 'grid-title-'+String(count);
        title.className = 'label';
        if (list[k].shorttitle) {
            list[k].title = list[k].shorttitle;
        }
        title.innerHTML += '<a href="###"><span>'+list[k].title+"</span></a>";
        if(list[k].thumbnail_width){
            el.style.width = list[k].thumbnail_width+'px';
            title.style.width = (list[k].thumbnail_width-10)+'px';
        } else {
            el.style.width = title.style.width = '80px';
        }
        var frame = document.createElement('DIV');
        frame.style.textAlign='center';
        if(list[k].thumbnail_height){
            frame.style.height = list[k].thumbnail_height+'px';
        }
        var img = document.createElement('img');
        img.src = list[k].thumbnail;
        if(list[k].thumbnail_alt) {
            img.alt = list[k].thumbnail_alt;
        }
        if(list[k].thumbnail_title) {
            img.title = list[k].thumbnail_title;
        }
        var link = document.createElement('A');
        link.href='###';
        link.id = 'img-id-'+String(count);
        if(list[k].url) {
            el.innerHTML += '<p><a target="_blank" href="'+list[k].url+'">'+fp_lang.preview+'</a></p>';
        }
        link.appendChild(img);
        frame.appendChild(link);
        el.appendChild(frame);
        el.appendChild(title);
        panel.appendChild(el);
        if(list[k].children) {
            var folder = new YAHOO.util.Element(link.id);
            folder.path = list[k].path;
            var el_title = new YAHOO.util.Element(title.id);
            folder.fs = list[k].children;
            folder.on('contentReady', function() {
                this.on('click', function() {
                    if(fp.fs.dynload) {
                        var fs = repository_client.fp[client_id].fs;
                        var params = [];
                        params['p'] = this.path;
                        params['env'] = repository_client.fp[client_id].env;
                        params['repo_id'] = fs.repo_id;
                        params['ctx_id'] = fp_config.contextid;
                        params['sesskey']= moodle_cfg.sesskey;
                        params['accepted_types'] = repository_client.fp[client_id].accepted_types;
                        params['client_id'] = client_id;
                        repository_client.loading(client_id, 'load');
                        var trans = YAHOO.util.Connect.asyncRequest('POST',
                                moodle_cfg.wwwroot+'/repository/ws.php?action=list', repository_client.req_cb, repository_client.postdata(params));
                    }else{
                        repository_client.view_as_icons(client_id, this.fs);
                    }
                });
            });
            el_title.on('contentReady', function() {
                this.on('click', function(){
                    folder.fireEvent('click');
                });
            });    
        } else {
            var el_title = new YAHOO.util.Element(title.id);
            var file = new YAHOO.util.Element(link.id);
            el_title.title = file.title = list[k].title;
            el_title.value = file.value = list[k].source;
            el_title.icon = file.icon  = list[k].thumbnail;
            if(fp.fs.repo_id) {
                el_title.repo_id = file.repo_id = fp.fs.repo_id;
            }else{
                el_title.repo_id = file.repo_id = '';
            }
            file.on('contentReady', function() {
                this.on('click', function() {
                    repository_client.select_file(this.title, this.value, this.icon, client_id, this.repo_id);
                });
            });
            el_title.on('contentReady', function() {
                this.on('click', function() {
                    repository_client.select_file(this.title, this.value, this.icon, client_id, this.repo_id);
                });
            });
        }
        count++;
    }
    container.appendChild(panel);
    repository_client.print_footer(client_id);
}
repository_client.print_footer = function(client_id) {
    var fs = this.fp[client_id].fs;
    var panel = document.getElementById('panel-'+client_id);
    var footer = document.createElement('DIV');
    footer.id = 'fp-footer-'+client_id;
    footer.innerHTML += this.create_upload_form(client_id);
    footer.innerHTML += this.paging(client_id, 'footer');
    panel.appendChild(footer);
    // add repository manage buttons here
    var oDiv = document.getElementById('repo-tb-'+client_id);
    if(!fs.nosearch) {
        var search = document.createElement('A');
        search.href = '###';
        search.innerHTML = '<img src="'+moodle_cfg.pixpath+'/a/search.png" /> '+fp_lang.search;
        oDiv.appendChild(search);
        search.onclick = function() {
            repository_client.search_form(client_id, fs.repo_id);
        }
    }
    // weather we use cache for this instance, this button will reload listing anyway
    if(!fs.norefresh) {
        var ccache = document.createElement('A');
        ccache.href = '###';
        ccache.innerHTML = '<img src="'+moodle_cfg.pixpath+'/a/refresh.png" /> '+fp_lang.refresh;
        oDiv.appendChild(ccache);
        ccache.onclick = function() {
            var params = [];
            params['env']=fs.env;
            params['sesskey']=moodle_cfg.sesskey;
            params['ctx_id']=fp_config.contextid;
            params['repo_id']=fs.repo_id;
            params['client_id']=client_id;
            repository_client.loading(client_id, 'load');
            var trans = YAHOO.util.Connect.asyncRequest('POST',
                    moodle_cfg.wwwroot+'/repository/ws.php?action=ccache', repository_client.req_cb, repository_client.postdata(params));
        }
    }
    if(fs.manage) {
        var mgr = document.createElement('A');
        mgr.innerHTML = '<img src="'+moodle_cfg.pixpath+'/a/setting.png" /> '+fp_lang.mgr;
        mgr.href = fs.manage;
        mgr.target = "_blank";
        oDiv.appendChild(mgr);
    }
    if(!fs.nologin) {
        var logout = document.createElement('A');
        logout.href = '###';
        logout.innerHTML = '<img src="'+moodle_cfg.pixpath+'/a/logout.png" /> '+fp_lang.logout;
        oDiv.appendChild(logout);
        logout.onclick = function() {
            repository_client.logout(client_id, fs.repo_id);
        }
    }
    if(fs.help) {
        var help = document.createElement('A');
        help.href = fs.help;
        help.target = "_blank";
        help.innerHTML = '<img src="'+moodle_cfg.pixpath+'/a/help.png" /> '+fp_lang['help'];
        oDiv.appendChild(help);
    }
}

repository_client.postdata = function(obj) {
    var str = '';
    for(k in obj) {
        if(obj[k] instanceof Array) {
            for(i in obj[k]) {
                str += (encodeURIComponent(k) +'[]='+encodeURIComponent(obj[k][i]));
                str += '&';
            }
        } else {
            str += encodeURIComponent(k) +'='+encodeURIComponent(obj[k]);
            str += '&';
        }
    }
    return str;
}

repository_client.stripHTML = function(str){
    var re= /<\S[^><]*>/g
    var ret = str.replace(re, "")
    return ret;
}
repository_client.popup = function(client_id, url) {
    repository_client.win = window.open(url,'repo_auth', 'location=0,status=0,scrollbars=0,width=500,height=300');
    repository_client.win.client_id = client_id;
    return false;
}
function repository_callback(id) {
    repository_client.req(repository_client.win.client_id, id, '');
    repository_client.win = null;
}
repository_client.logout = function(client_id, repo_id) {
    var params = [];
    params['repo_id'] = repo_id;
    params['client_id'] = client_id;
    var trans = YAHOO.util.Connect.asyncRequest('POST', moodle_cfg.wwwroot+'/repository/ws.php?action=logout',
            repository_client.req_cb, repository_client.postdata(params));
}
repository_client.download = function(client_id, repo_id) {
    var fp = repository_client.fp[client_id];
    var title = document.getElementById('newname-'+client_id).value;
    var file = document.getElementById('fileurl-'+client_id).value;
    repository_client.loading(client_id, 'download', title);
    var params = [];
    if(fp.itemid){
        params['itemid']=fp.itemid;
    }
    params['env']=fp.env;
    params['file']=file;
    params['title']=title;
    params['sesskey']=moodle_cfg.sesskey;
    params['ctx_id']=fp_config.contextid;
    params['repo_id']=repo_id;
    params['client_id']=client_id;
    var trans = YAHOO.util.Connect.asyncRequest('POST',
            moodle_cfg.wwwroot+'/repository/ws.php?action=download',
            repository_client.download_cb,
            repository_client.postdata(params));
}
repository_client.download_cb = {
    success: function(o) {
         var data = repository_client.parse_json(o.responseText, 'download_cb');    
         var panel = new YAHOO.util.Element('panel-'+data.client_id);
         if(data && data.e) {
             panel.get('element').innerHTML = data.e;
             return;
         }
         repository_client.end(data.client_id, data);
    }
}
repository_client.end = function(client_id, obj) {
    var fp = repository_client.fp[client_id];
    if(fp.env=='filepicker') {
        fp.target.value = obj['id'];
    }else if(fp.env=='editor'){
        fp.target.value = obj['url'];
        fp.target.onchange();
    }
    fp.formcallback(obj);
    fp.hide();
    repository_client.viewfiles(client_id);
}
repository_client.viewfiles = function(client_id) {
     var repo = repository_client.fp[client_id];
    if(repo.view_status) {
        repository_client.view_as_list(client_id);
    } else {
        repository_client.view_as_icons(client_id);
    }
}
repository_client.create_upload_form = function(client_id) {
    var str = '';
    var fs = repository_client.fp[client_id].fs;
    if(fs.upload) {
        var id = fs.upload.id+'_'+client_id;
        str += '<div id="'+id+'_div" class="fp-upload-form">';
        str += '<form id="'+id+'" onsubmit="return false">';
        str += '<label for="'+id+'_file">'+fs.upload.label+': </label>';
        str += '<input type="file" id="'+id+'_file" name="repo_upload_file" />';
        str += '<p class="fp-upload-btn"><a href="###" onclick="return repository_client.upload(\''+client_id+'\');">'+fp_lang.upload+'</a></p>';
        str += '</form>';
        str += '</div>';
    }
    return str;
}
repository_client.upload = function(client_id) {
    var fp = repository_client.fp[client_id];
    var u = repository_client.fp[client_id].fs;
    var id = u.upload.id+'_'+client_id;
    var aform = document.getElementById(id);
    var parent = document.getElementById(id+'_div');
    var d = document.getElementById(id+'_file');
    if(d.value!='' && d.value!=null) {
        var container = document.createElement('DIV');
        container.id = id+'_loading';
        container.style.textAlign='center';
        var img = document.createElement('IMG');
        img.src = moodle_cfg.pixpath+'/i/progressbar.gif';
        var para = document.createElement('p');
        para.innerHTML = fp_lang.uploading;
        container.appendChild(para);
        container.appendChild(img);
        parent.appendChild(container);
        YAHOO.util.Connect.setForm(aform, true, true);

        var trans = YAHOO.util.Connect.asyncRequest('POST',
                moodle_cfg.wwwroot+'/repository/ws.php?action=upload&itemid='+fp.itemid
                    +'&sesskey='+moodle_cfg.sesskey
                    +'&ctx_id='+fp_config.contextid
                    +'&repo_id='+u.repo_id
                    +'&client_id='+client_id,
                repository_client.upload_cb);
    }else{
        alert(fp_lang.filenotnull);
    }
}
repository_client.upload_cb = {
upload: function(o) {
        var ret = repository_client.parse_json(o.responseText, 'upload');    
        client_id = ret.client_id;
        if(ret && ret.e) {
            var panel = new YAHOO.util.Element('panel-'+client_id);
            panel.get('element').innerHTML = ret.e;
            return;
        }
        if(ret) {
            alert(fp_lang.saved);
            repository_client.end(client_id, ret);
        }
    }
}
repository_client.parse_json = function(txt, source) {
    try {
        var ret = YAHOO.lang.JSON.parse(txt);
    } catch(e) {
        alert(fp_lang.invalidjson+' - |'+source+'| -'+this.stripHTML(txt));
    }
    return ret;
}
repository_client.search_form = function(client_id, id) {
    var fp = repository_client.fp[client_id];
    var params = [];
    params['env']=fp.env;
    params['sesskey']=moodle_cfg.sesskey;
    params['client_id']=client_id;
    params['ctx_id']=fp_config.contextid;
    params['repo_id']=id;
    var trans = YAHOO.util.Connect.asyncRequest('POST',
            moodle_cfg.wwwroot+'/repository/ws.php?action=searchform',
            repository_client.search_form_cb,
            repository_client.postdata(params));
}
repository_client.search_form_cb = {
success: function(o) {
     var data = repository_client.parse_json(o.responseText, 'search_form_cb');
     var el = document.getElementById('fp-search-dlg');
     var fp = repository_client.fp[data.client_id];
     if(el) {
         el.innerHTML = '';
     } else {
         var el = document.createElement('DIV');
         el.id = 'fp-search-dlg';
     }
     var div1 = document.createElement('DIV');
     div1.className = 'hd';
     div1.innerHTML = fp_lang.searching+"\"" + repository_listing[data.client_id][fp.fs.repo_id].name + '"';
     var div2 = document.createElement('DIV');
     div2.className = 'bd';
     var sform = document.createElement('FORM');
     sform.method = 'POST';
     sform.id = "fp-search-form";
     sform.action = moodle_cfg.wwwroot+'/repository/ws.php?action=search';
     sform.innerHTML = data['form'];
     div2.appendChild(sform);
     el.appendChild(div1);
     el.appendChild(div2);
     document.body.appendChild(el);
     var dlg_handler = function() {
         var client_id=dlg_handler.client_id;
         repository_client.fp[client_id].viewbar.set('disabled', false);
         repository_client.loading(client_id, 'load');
         YAHOO.util.Connect.setForm('fp-search-form', false, false);
         this.cancel();
         var url = moodle_cfg.wwwroot+'/repository/ws.php?action=search&env='+dlg_handler.env
                +'&client_id='+client_id;
         var trans = YAHOO.util.Connect.asyncRequest('POST', url,
             repository_client.req_cb);
     }
     dlg_handler.client_id = data.client_id;
     dlg_handler.env = fp.env;
     var dlg = new YAHOO.widget.Dialog("fp-search-dlg",{
        postmethod: 'async',
        draggable: true,
        width : "30em",
        fixedcenter : true,
        zindex: 766667,
        visible : false,
        constraintoviewport : true,
        buttons : [
        {
            text:fp_lang.submit,
            handler: dlg_handler,
            isDefault:true
        },
        {text:fp_lang.cancel,handler:function(){this.cancel()}}
        ]
    });
    dlg.render();
    dlg.show();
}
}
var mdl_in_array = function(el, arr) {
    for(var i = 0, l = arr.length; i < l; i++) {
        if(arr[i] == el) {
            return true;
        }
    }
    return false;
}

// will be called by filemanager or htmleditor
function open_filepicker(id, params) {
    var r = repository_client.fp[id];
    if(!r) {
        // passing parameters
        r = new repository_client();
        r.env = params.env;
        r.target = params.target;
        if(params.itemid){
            r.itemid = params.itemid;
        } else if(tinyMCE && id2itemid[tinyMCE.selectedInstance.editorId]){
            r.itemid = id2itemid[tinyMCE.selectedInstance.editorId];
        }
        // setup callback function
        if(params.callback) {
            r.formcallback = params.callback;
        } else {
            r.formcallback = function() {};
        }
        // write back
        repository_client.fp[id] = r;
        // create file picker (html elements and events)
        r.create_filepicker(id);
    } else {
        r.target = params.target;
        r.show();
    }
    if(params.filetype) {
        if(params.filetype == 'image') {
            r.accepted_types = file_extensions.image;
        } else if(params.filetype == 'video' || params.filetype== 'media') {
            r.accepted_types = file_extensions.media;
        } else if(params.filetype == 'file') {
            r.accepted_types = '*';
        }
    } else {
        r.accepted_types = '*';
    }
    return r;
}