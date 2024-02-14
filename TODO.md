# To-Do List

- TODO: [backups|job] handle timeout (maybe let set by user?) + handle retries
- TODO: [webdav-locks/notifications] cleanup function for expired locks + old notifications
- TODO: installer

## Check Todo

- FIXME: on refresh/page load colors jump etc. -> maybe only enable animations after load + set default background color so it does not switch from white to dark background
- TODO: check if sessions are cleared after some time from database
- TODO: [general] check timezones

## Docs Todo

- DOC: document: https://sabre.io/dav/webservers/

### Future

- FIX: unify colors of buttons everywhere -> decide where primary, accent, secondary, neutral, ...
- FIX: [factory|file] force directory to have same user as file
- REFACTOR: [webdav] copy stream uses a lot of memory?
- REFACTOR: [webdav] does store use a lot of memory? Plugin to write data to tmp file? Other solutions? Try with large file
- REFACTOR: [filesystem] maybe dont use fopen and use storage read/writeStream methods?
- FEAT: why on edit views dont return back
- FIX: [previousUrl]: handle if previous is confirm-password
- FEAT: [versions] max amount versions -> delete oldest
- FEAT: [backups] to google drive (https://github.com/masbug/flysystem-google-drive-ext), one drive, webdav, scp. providers can be toggled
- FEAT: [backups] application backups (database, files, etc.) see spatie/backup
- FEAT: [general] make email server optional
- FEAT: [admin-panel] + on delete user check that is not last admin (also when user deletes itself)
- FEAT: [backups] add option for target name for backup configuration file

## Ideas:

- IDEA: [dropdown-pos-helper]: only 2 positions, not 3 (bottom and top, not left)
- IDEA: total disk space per user + quota
- IDEA: [web-dav-users{naming}]: maybe combine "webdav" instead of "web-dav"
- IDEA: [access-user] auto disable access user when inactive
- IDEA: [access-user] option to set whether directories can be viewed
- IDEA: tags per file
- IDEA: Favorites
- IDEA: ownership transfer of single files -> remove from access groups and check all other permissions!
- IDEA: [webdav|virtual-directory] maybe dont build full tree here? just return a virtual directory without children -> https://github.com/nextcloud/server/blob/9416778ed2e21e961d1a0c5222f718c545138671/apps/dav/lib/Connector/Sabre/ServerFactory.php#L141
