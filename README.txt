Provider Settings
-----------------
RRS-Feeds can be very slow to load. For that reason it is strongly recommended to increase PHP's memory limit:
memory_limit = 256M;

This is used when manually getting new Pages by clicking "Get new Pages" in /dashboard/rss_blog.
When using the Automated Job "Get new Pages" only this setting isn't necessary.


Add-On Installation
-------------------
1. Unzip the toess_lab_rss_blog.zip into /packages folder and upload to the server.
2. Navigate to Dashboard -> extend concrete5 and click "Install" at the right side of "toesslab - RSS Blog"
3. Navigate to Dashboard -> System & Settings -> Optimization -> Automated Jobs
4. Click on the watch symbol (Automate this Job) beside of "Get new Pages".
5. Copy the Job URL as a whole and click "Save"
6. Install the copied URL as a Cron Job at your providers Cron Job Feature.
Recommendation:
It is useful to run that Job every few minutes. (In this example every 5 minutes):
*/5 * * * * /usr/local/bin/wget 'http://packages.ch/index.php/ccm/system/jobs/run_single?auth=c495b375ffd1222ebcbfb672bf2cce5b&jID=8' >/dev/null 2>&1
The path to wget can vary from one server to another. Please ask your provider for more information & help.


Add-On Settings
---------------
Navigate to Dashboard -> toesslab - RSS Blog -> RSS Blog Settings
1. Provide a valid RSS-Feed URL.
2. Select a parent Page where Blog Entry Pages will be added below.
3. Select Page Type for Blog Entries.
   Remember to edit the Page Type Output to your needs before using the Add-On.
   a. Navigate to Dashboard -> Pages & Themes
   b. Click "Output" besides of "toesslab Blog Entry"
   c. Click "Edit Defaults" besides of "Right Sidebar"
   d. Edit the page as any other concrete5 Page
   If you're using another Page Type don't add "Page Title" Block, because the Add-On is using it's own one.
4. Select the Size of the desired Thumbnails defined in the Thumbnail Settings (/dashboard/system/files/thumbnails).
5. Add some HTML to be added below each Blog Entry Page (Optional)