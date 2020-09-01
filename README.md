TemplaVoilà! Plus - Classic Edition
===================================






.

**FAL Migrator:**
How does it work on its current state?
Simply goes through all datastructures, collects file fields and then 
iterates tt_contents/FCEs of that type. In each analyses saved flexform data, takes referenced filename and 
instantiates as FAL object. Then inserts FAL reference into db.
What it doesn't do is updating your datastructures - you have to do this by yourself.

NOTE

- You have to use old format datastructures when running the process - byt that it recognizes which fields to process
and reads the config. Then checkout the updated dses when finished, to see the operation result.
And it expects them as staticDS (in files).

- It may not process properly these irre/nested fces, I didn't finish that when I realized the results.
(But it's not very important, when it's useless anyway). Migrate to fal only if you don't have any repetitive contents 
or have some other idea to make that work...

- Also it doesn't process pages records. I focused on ttcontents, but sometimes people references files to page template
tv properties. There was a plan to extend that functionality, but guess what, yes, I didn't finish and probably I won't.
Do this if you need and send me a pull request.

.

***How to run:***
- may be run as scheduler task or better standalone like that for easier debugging & display stats:

````
tvFal = PAGE
tvFal.typeNum = 1112
tvFal.10 = USER
tvFal.10.userFunc = Ppi\TemplaVoilaPlus\Controller\Update\FalUpdateController->execute
````

https://example.local/?type=1112

https://example.local/?type=1112&stats=1

It works in basics. 



FAL datastructure example:

````
<field_image type="array">
        <tx_templavoilaplus type="array">
            <title>Image</title>
                ...
        </tx_templavoilaplus>
        <TCEforms type="array">
            <label>Image</label>
            <config type="array">
                <!--
                <type>group</type>
                <internal_type>file</internal_type>
                <allowed>gif,png,jpg,jpeg</allowed>
                <max_size>1000</max_size>
                <uploadfolder>uploads/tx_templavoilaplus</uploadfolder>
                <show_thumbs>1</show_thumbs>
                <size>1</size>
                <maxitems>1</maxitems>
                <minitems>0</minitems>
                -->

                
                <type>inline</type>
                <foreign_table>sys_file_reference</foreign_table>
                <foreign_field>uid_foreign</foreign_field>
                <foreign_sortby>sorting_foreign</foreign_sortby>
                <foreign_table_field>tablenames</foreign_table_field>
                <foreign_match_fields>
                    <fieldname>image</fieldname>	<-- this tv field id -->
                </foreign_match_fields>
                <foreign_label>uid_local</foreign_label>
                <foreign_selector>uid_local</foreign_selector>
                <overrideChildTca>
                    <columns>
                        <uid_local>
                            <config>
                                <appearance>
                                    <elementBrowserType>file</elementBrowserType>
                                    <elementBrowserAllowed></elementBrowserAllowed>
                                </appearance>
                            </config>
                        </uid_local>
                    </columns>
                </overrideChildTca>
                <filter>
                    <userFunc>TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter->filterInlineChildren</userFunc>
                    <parameters>
                        <allowedFileExtensions>gif,png,jpg,jpeg</allowedFileExtensions>	<-- from old 'allowed' -->
                        <disallowedFileExtensions></disallowedFileExtensions>
                    </parameters>
                </filter>
                <appearance>
                    <useSortable>1</useSortable>
                    <headerThumbnail>
                        <field>uid_local</field>
                        <width>45</width>
                        <height>45c</height>
                    </headerThumbnail>
                    <enabledControls>
                        <info>1</info>
                        <new>0</new>
                        <dragdrop>1</dragdrop>
                        <sort>0</sort>
                        <hide>1</hide>
                        <delete>1</delete>
                    </enabledControls>
                </appearance>
            </config>
        </TCEforms>
    </field_image>
````



.

.

.





Original TV+ Readme:
----------


[![license](https://img.shields.io/github/license/pluspol-interactive/templavoilaplus.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0-standalone.html)
[![version](https://img.shields.io/badge/TER_version-7.3.5-green.svg)](https://extensions.typo3.org/extension/templavoilaplus)
[![packagist](https://img.shields.io/packagist/v/templavoilaplus/templavoilaplus.svg)](https://packagist.org/packages/templavoilaplus/templavoilaplus)

TeamplaVoilà! Plus is a templating extension for the TYPO3 content management system. It is the follow up of the popular
TemplaVoilà! extension from Kasper Skårhøj prepared for modern versions of TYPO3.

Language files
--------------

If you like to help with the translation of the extension, please visit https://github.com/pluspol-interactive/templavoilaplus-languagefiles

**NOTE:**
*   You need to run the Migration Script (The update button in ExtensionManager) to fully migrate from TemplaVoilà 1.8/1.9/2.0.
    You can find the migration Guide here: https://docs.typo3.org/typo3cms/extensions/templavoilaplus/Migration/Index.html
