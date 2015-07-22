=== Custom Field Template ===
Contributors: Jeremy Zhang
Donate link: https://zhangzike.wordpress.com/
Tags: custom field, custom fields, custom, fields, field, template, meta, custom field template, custom post type
Requires at least: 1.0
Tested up to: 4.2
Stable tag: 2.3.4
License: GPLv2 or later

The Custom Field Template plugin extends the functionality of custom fields.

== Description ==

The Custom Field Template plugin adds the default custom fields on the Write Post/Page. The template format is almost same as the one of the rc:custom_field_gui plugin. The difference is following.

* You can set any number of the custom field templates and switch the templates when you write/edit the post/page.
* This plugin does not use the ini file for the template but set it in the option page of the plugin.
* Support for TinyMCE in the textarea.
* Support for media buttons in the textarea. - requires at least 2.5.
* Support for multiple fields with the same key.
* Support for hideKey and label options.
* You can see the full option list in the setting page of the plugin.
* You can customize the design of custom field template with css.
* You can replace custom keys by labels.
* You can use wpautop function.
* You can use PHP codes in order to set values. (experimental, `code = 0`)
* You can set an access user level in each field. (`level = 1`)
* Supprt for inserting custom field values into tags automatically. (`insertTag = true`)
* Adds [cft] Shortcode to display the custom field template. (only shows the attributes which have `output = true`)
* Adds template instruction sections.
* Adds the value label option for the case that values are diffrent from viewed values. (`valueLabel = apples # oranges # bananas`)
* Adds the blank option. (`blank = true`)
* Adds the break type. Set CSS of '#cft div'. (`type = break`)
* Adds [cft] Shortcode Format.
* Adds the sort option. (`sort = asc`, `sort = desc`, `sort = order`)
* Support for Quick Edit of custom fields. (tinyMCE and mediaButton are not supported yet)
* Support for the custom field search. (only shows the attributes which have `search = true`.)
* Adds [cftsearch] Shortcode Format. (under development)
* Adds PHP codes for the output value. (`outputCode = 0`)
* Adds PHP codes before saving the values. (`editCode = 0`)
* Adds the save functionality.
* Adds the class option. (`class = text`)
* Adds the auto hook of `the_content()`. (experimental)
* You can use the HTML Editor in the textarea. (`htmlEditor = true`)
* Adds the box title replacement option.
* Adds the select option of the post type.
* Adds the value count option.
* Adds the option to use the shortcode in the widhet.
* Adds the attributes of JavaScript Event Handlers. (`onclick = alert('ok');`)
* Adds the Initialize button.
* Adds the attributes of before and after text. (`before = blah`, `after = blah`)
* Adds the export and import functionality.
* Adds the style attribute. (`style = color:#FF0000;`)
* Adds the maxlength attribute. (`maxlength = 10`)
* Adds the attributes of multiple fields. (`multiple = true`, `startNum = 5`, `endNum = 10`, `multipleButton = true`)
* Adds the attributes of the date picker in `text` type. (`date = true`, `dateFirstDayOfWeek = 0`, `dateFormat = yyyy/mm/dd`)
* Adds the filter of page template file names (Thanks, Joel Pittet).
* Adds the attribute of `shortCode` in order to output the shortcode filtered values. (`shortCode = true`)
* Adds the attribute of `outputNone` in case there is no data to output. (`outputNone = No Data`)
* Adds the attribute of `singleList` attribute in order to output with `<ul><li>` if the value is single. ex) `singleList = true`
* Adds the file upload type. (`type = file`)
* Adds the fieldset type. (`type = fieldset_open`, `type = fieldset_close`)
* Adds the option to deploy the box in each template.

Localization

* Belorussian (by_BY) - [Marcis Gasuns](http://www.fatcow.com/)
* Catalan (ca) - [Andreu Llos](http://andreullos.com/)
* Czech (cs_CZ) - [Jakub](http://www.webees.cz/)
* German (de_DE) - F J Kaiser
* Spanish (es_ES) - [Dario Ferrer](http://www.darioferrer.com/)
* Farsi (fa_IR) - [Mehdi Zare](http://sabood.ir/)
* French (fr_FR) - Nicolas Lemoine
* Hungarian (hu_HU) - [Balazs Kovacs](http://www.netpok.hu)
* Indonesian (id_ID) - [Masino Sinaga](http://www.openscriptsolution.com/)
* Italian (it_IT) - [Gianni Diurno](http://gidibao.net/)
* Japanese (ja) - [Hiroaki Miyashita](http://wpgogo.com/)
* Dutch (nl_NL) - [Rene](http://wordpresswebshop.com/)
* Polish (pl_PL) - [Difreo](http://www.difreo.pl/)
* Brazilian Portuguese (pt_BR) - [Caciano Gabriel](http://www.gn10.com.br/)
* Russian (ru_RU) - [Sonika](http://www.sonika.ru/blog/)
* Swedish (sv_SE) - [Pontus Carlsson](http://www.fristil.se/)
* Turkish (tr_TR) - [Omer Faruk](http://ramerta.com/)
* Ukranian (uk_UA) - [Andrew Kovalev](http://www.portablecomponentsforall.com)
* Uzbek (uz_UZ) - [Alexandra Bolshova](http://www.comfi.com/)
* Chinese (zh_CN) - hurri zhu

If you have translated into your language, please let me know.

* [Japanese Custom Field Template Manual](http://ja.wpcft.com/)

== Installation ==

1. Copy the `custom-field-template` directory into your `wp-content/plugins` directory
2. Activate the plugin through the `Plugins` menu in WordPress
3. Edit the options in `Settings` > `Custom Field Template`
4. That's it! :)

== Frequently Asked Questions ==

= How can I use this plugin? =

The template format is basically same as the one of the rc:custom_field_gui plugin.
See the default template and modify it.

= How can I display the custom fields? =

1. Use the cft shortcode. In the edit post, write down just `[cft]`. If you would like to specify the post ID, `[cft post_id=15]`. You can also set the template ID like `[cft template=1]`.
2. Do you want to insert a particular key value? Use `[cft key=Key_Name]`.
3. If you set the format of the custom fields, use `[cft format=0]`.
4. Auto Hook of `the_content()` in the option page of this plugin may help you do this. You can use [cft] shortcodes here. You can switch the cft formats in each category.

== Changelog ==

= 1.0.0 =
*  .

== Screenshots ==

1. Custom Field Template - Settings
2. Custom Field Template

== Known Issues / Bugs ==

== Uninstall ==

1. Deactivate the plugin
2. That's it! :)
