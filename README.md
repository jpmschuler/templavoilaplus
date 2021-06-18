TemplaVoila! Plus - Classic Edition for 10.4
============================================

This is a fork of Pluspol's TemplaVoila+ intended to be simply the same TV as it was for TYPO3 9 and before,
but compatible with 10.4.
Main point of doing this is to make updating of older projects as painless and quick as possible.

It bases on TV+ 7.3.3 (latest from 7.x) and tries to keep all classic functionality working as-is,
unlike the 8.x with its breaking architecture changes.

If you want to build a new site, use original TV from ext repo.



.

***IMPORTANT INFO:***
 
BETA - basically works - may need more fixes in some use cases, which I didn't try in my projects


__What works:__

- __Frontend renders__ as expected, pages, fces

- __Page module__ now works without issues (fixed drag&drop, clipboard, referencing new ce in proper template fields)

- Surprise - I wrote __preview renderer for flux/fluidcontents,__ to show them in TV's Page module the same way as in default Page module.
So you can use both TV/FCE and Flux/Fluid CE simultaneously (not tested yet with languages, workspaces etc).
This can help with more painless successively migration of contents to Flux one by one after running site to live.
(At least I hope so)

- Mapping module: __mapping fields__ - no issues. I didn't test too much adding new fields from gui - I always just write xml. So not sure.
Try and tell me, or fix & push me changes.



 __What doesn't, or I don't know__ if works:

- __Languages, versioning / workspaces__. I didn't test translated contents at all, because I don't have l10n in this project
and I just didn't need to find time for that.
If you need, try to test and finish.

- __Images in FCEs__ doesn't show by default (also other files attached by old upload field). __[IRRE nesting case]__
Background: I tried to automigrate everything to FAL, but I stuck on nested/repetitive FCEs,
which exceeds nesting limit when switching fields to fal irre relations. (so it's typo3's backend architecture limitation problem)
And for now I abandoned this idea. (You can find my migrator controller and try to use, it might fit your needs if you don't have any irre fces).

	--__Solution 1. manually:__ *(if you have irre fces, but not many of them)*
	
	(Investigate if you have such) and remove from your FCEs all IRRE subfields, which contains file-attach field in it. Extract these nested parts
	to separate sub-FCEs with FAL fields instead.
	NOTE, that you will have to re-edit all these contents and recreate these subelements by hand, so it may be a real pain in the ass, if you have many of them...
	You can always try to write some migrator for that.

	-- __Solution 2. semi-automatic:__ *(if you have many irre fces and recreating them by hand is not an option)*
	
	My B-plan was to extract & __restore that missing TCA Group/File type__ - so I took code parts from 9.5 and integrated
	them as xclasses beside current 10.4 parts. You can __try to run this__ using ext conf option __compatibility.restoreTCAGroupFileType = 1__
	(ext manager / localconf). The code is in Classes/Ext/Backend/. Works well for me.
	As for now it basically works as expected and doesn't conflict with anything. (though needs some tests to be sure).
	This approach is kinda regressive, but I can't see a way to insist the full fal migration when the system just doesn't
	want to cooperate.

	-- __Solution 3. automatic:__ *(if you don't have irre-nested fces with images)*
	
	If you're not in such problematic situation, and only have simple 1-level not nested fces with file-attach, @see *FAL Migrator* section below


- TV needs __typo3db_legacy__ ext - too much rewriting for now, and I only needed to have TV usable for now, I'm not pointing to make
this ext fit modern t3 coding standards. There are some compromises. Use it keeping in mind migrating to Fluid in near future.


.

Feel free to help me with this, while 8.x is still not usable, and we all need working TV, like, now.

w010

.

.

**FAL Migrator:**
How does it work on its current state?
Simply goes through all datastructures, collects file fields and then 
iterates tt_contents/FCEs of that type. In each analyses saved flexform data, takes referenced filename and 
instantiates as FAL object. Then inserts FAL reference into db.
What it doesn't do is updating your datastructures - you have to do this by yourself.

NOTE

- You have to use old format datastructures when running the process - by that it recognizes which fields to process
and reads the config. Then checkout the updated dses when finished, to see the operation result.
And it expects them as staticDS (in files).

- It may not process properly these irre/nested fces, I didn't finish that when I realized the results.
(But it's not very important, when it's useless anyway).
__MIGRATE TO FAL ONLY IF YOU DON'T HAVE ANY REPETITIVE CONTENTS__
or have some other idea to make that work...
Otherway - use the xclasses mentioned in solution 2, to restore classic file upload TCA type and keep it old way for now

- Also it doesn't process pages records. I focused on ttcontents, but sometimes people references files to page template
tv properties. There was a plan to extend that functionality, but guess what, yes, I didn't finish and probably I won't,
unless I have such case somewhere.
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
--------------------

TeamplaVoilà! Plus is a templating extension for the TYPO3 content management system. It is the follow up of the popular
TemplaVoilà! extension from Kasper Skårhøj prepared for modern versions of TYPO3.

Language files
--------------

If you like to help with the translation of the extension, please visit https://github.com/pluspol-interactive/templavoilaplus-languagefiles

**NOTE:**
*   You need to run the Migration Script (The update button in ExtensionManager) to fully migrate from TemplaVoilà 1.8/1.9/2.0.
    You can find the migration Guide here: https://docs.typo3.org/typo3cms/extensions/templavoilaplus/Migration/Index.html
