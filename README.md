# MyBB Warning Display

A lightweight MyBB plugin to show warning messages on posts in a thread.

## Installation

1. Download the latest release of the project. Unzip the download and open the directory. Inside will be a "upload" folder. Go into the folder and upload its contents to your MyBB installation.

2. Go to your MyBB's AdminCP plugin page. `AdminCP > Configuration > Plugins`

3. Under "Inactive Plugins", find "Warning Display" and click "Install & Activate"


## Configuration
The warning display is easily customizable via a template in the AdminCP. `AdminCP > Templates & Style > Templates > Global Templates > warning_display_template`

To customize where the message is displayed in the postbit, look for `{$post['warning_display']}` in `AdminCP > Templates & Style > Templates > Default Templates > Post Bit Templates > postbit`

## Example

![Example](/docs/example.png)
