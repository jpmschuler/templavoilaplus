TemplaVoilà! Plus - Classic Edition for 10.4
============================================

This is a fork of Pluspol's TemplaVoila+ intended to be simply the same TV as it was for TYPO3 9 and before,
but compatible with 10.4.
Main point of doing this is to make updating of older projects as painless and quick as possible.

It bases on TV+ 7.3.3 (latest from 7.x) and tries to keep all classic functionality working as-is,
unlike the 8.x with its breaking architecture changes.

If you want to build a new site, use original TV from ext repo.



.

**IMPORTANT:**
 
WIP 

This code is still in early state, page module basically works, frontend rendering too. Images in FCEs doesn't show, some FAL-migrator
is needed to be added (10.4 dropped non-FAL file relations)
Mapping module is not ready yet.

Feel free to help me with this, while 8.x is still not usable, and we all need working TV now.

w010

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
