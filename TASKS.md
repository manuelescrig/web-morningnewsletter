# TASKS
List of task for claude cursor to implement.

RULES: 
- For each task, create a commit so then I have the flexibility to revert it if I don't like what I see.
- Check for tasks below and implmement them following the orders.
- Once the task has been complated, mark the task complated in this .md file.
- Keep implemeting these tasks in the project allways doing a good deep dive of the project and thinking really hard to understand what the user wants.
- Do not stop until you've finished implementing these tasks.

## TASKS

### ~~Add Data Source Modal improvements~~ ✅ COMPLETED
- The All category has a border. Remove it. All the category should behave the same, in fact remove all the borders in regular state and hover state for those filter categories.

### ~~Add Data Source Modal improvements~~ ✅ COMPLETED
- The source-card box has a .border-gray-200, remove it. It looks nicer without it. Also  no need for the category tag in there. It's just too much info and redundant.

### ~~Implement Finance sources~~ ✅ COMPLETED
- I belive there's a document explaining how to add a source, otherwise we will create one because we are going to add more sources.
- I want a Stock source that uses a free API and the user can search for stocks and then it adds the price of the stock to the newsletters. Kind of like with bitcoins but with Stocks.
- When implementing a new source please check how the current source are working and to the same thing.
- Do a double check to see what you are missing and please fix it. I want the user to select the Stock Source, to have the option to search for a stock with autocompletion and then select it and add it to the newsletter and recieve it.
- Make sure to make it editable for the user and to add it to the Sources screen so the admin can enable/disable it.

### ~~Implement News Sources~~ ✅ COMPLETED
- I belive there's a document explaining how to add a source, otherwise we will create one because we are going to add more sources.
- I want to give the user to add an RSS as a news source, the user can put a RSS, then the RSS will be validated and if it is valid add it. The user can choose the number of the RSS items to be fetched, the options houls be like 1,3,5. Then if the RSS is valid the user will able to receive in the newsletter those items. Make them look nice on the newsletter and try to match the style we have.
- Do a double check to see what you are missing and please fix it. I want the user to select the Stock Source, to have the option to search for a stock with autocompletion and then select it and add it to the newsletter and recieve it.
- Make sure to make it editable for the user and to add it to the Sources screen so the admin can enable/disable it.

### ~~Update Add Data Source & Newsletter Settings~~ ✅ COMPLETED
- Update Add Data Source & Newsletter Settings modals to match on the screeen in smaller screens, currently there/s no padding, put some padding to make it look more natural. Also remove the blur in the background and put a shared of black. Currently it's not showing it.

### Improve Crypto and Finance Sources 
- I want for crypto and finance sources to give the user the ability to show their holding value. Something like Show holding value and the ability to put 0.5x of the asset or something like 100x and then if the user has that enabled it will be calculated and shown in the preview and in the email newsletter. Format the numbers properly. And make this look nice.



### ~~Remove custom.css~~ ✅ COMPLETED
- We already have the dashboard.css so let's move all the code from custom.css to dashboard.css, I forgot about that. Also update all the places were we use it to make sure it works. Double check where we use custom.css and change it to dashbaord.css

### ~~Clean up files~~ ✅ COMPLETED
- Clean up files, looks like these files are no longer needed. If they are needed, do the refacotring and them remove them. I do not want to execute code on the server, I mean the .sh file.
convert-pill-classes.php
pill-button-mapping.md
update-pill-buttons.sh

### ~~Clean up more files~~ ✅ COMPLETED
- Clean up files, looks like these files are no longer needed. 
debug-scheduler.php
fix-scheduler-timezone.patch
test-scheduler.php

### ~~Implement constants~~ ✅ COMPLETED
- These are define constants, can you go one by one and make sure they are implement it and working in the dashboard?
Make sure you implement and test them and do not finish until they are working.

// Newsletter Configuration
define('MAX_DAILY_TIMES', 4); // Maximum number of times a newsletter can be sent per day
define('MAX_NEWSLETTERS_PER_USER', 10); // Maximum number of newsletters per user

// Time Configuration
define('DEFAULT_SEND_TIME', '06:00'); // Default newsletter send time
define('DEFAULT_TIMEZONE', 'UTC'); // Default timezone for new users // Remove this one, it should be taken from the user, I do not want to hard-code this.

// Email Configuration
define('EMAIL_FROM_NAME', 'MorningNewsletter');
define('EMAIL_FROM_ADDRESS', 'noreply@morningnewsletter.com');
define('EMAIL_REPLY_TO', 'support@morningnewsletter.com');

// Plan Limits
define('FREE_PLAN_SOURCE_LIMIT', 1);
define('STARTER_PLAN_SOURCE_LIMIT', 5);
define('PRO_PLAN_SOURCE_LIMIT', 15);
define('UNLIMITED_PLAN_SOURCE_LIMIT', 1000);

// Registration Configuration
define('REQUIRE_EMAIL_VERIFICATION', true);
define('REGISTRATION_RATE_LIMIT', 5); // Max registrations per IP per 5 minutes
define('REGISTRATION_RATE_WINDOW', 300); // 5 minutes in seconds

// Session Configuration
define('SESSION_LIFETIME', 86400); // 24 hours
define('REMEMBER_ME_DURATION', 2592000); // 30 days

// API Rate Limits
define('API_RATE_LIMIT_PER_MINUTE', 60);
define('API_RATE_LIMIT_PER_HOUR', 1000);

// File Upload Limits
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour
define('CACHE_DIRECTORY', __DIR__ . '/../cache');

// Debug Mode
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);
define('ERROR_LOG_PATH', __DIR__ . '/../logs/error.log');

### ~~Remove lucide icons~~ ✅ COMPLETED
- Let's remove all the lucide icons and go back to the font-awasome. 

### ~~Create New Newsletter box~~ ✅ COMPLETED
- The Create New Newsletter box on the dashboard looks different when openning it for the first time than if we close it and openning it again, it should always look the same. Maybe we have redundant code so make sure it always looks the same and we don't have repeated code for this. It should look like the first time. And on top of that we don-t want the border: 1px solid #e5e7eb; for the add plus icon button and we don't want the border neither, this could be the style class="px-3 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 remove-time-btn" and put the same border radious as the plus button. 

### ~~Newsletter Settings modal~~ ✅ COMPLETED
- Update the design. I'm not sure how to improve it but bottom part with the delete box looks bad. Too big and out of place so think about a good design practice and where it should be implement it and do it.




### ~~Refactor Dashboard Pill Buttons to Use Centralized CSS Variables~~ ✅ COMPLETED
I want to have everywhere we have a pill button either the blue blackground or the grayish/white backgroudn. Ok, go trough the whole dashboard files and make sure where we have the grayish bakcground to 
  create a new background color constant if we don't have it and use it everywhere in those places, also do the same for the other button properties, basically I'd like to have in the custom.css the option to 
  customize from there all the buttons from the dashboard, like the 2 types or 3 types and fromt custom.css just chaning one property make it bigger, change the background color, the font size, weidght or 
  color. All those things. Slowly I want to get rid off tailwind so this is the first step. Make sure and double check that all of them are done.

### ~~Fix delivery configuration~~ ✅ COMPLETED
Schedule Status
Current Time:
Jul 23, 9:37 AM
Next Send:
Jul 24, 7:00 AM
Last Sent:
Jul 23, 9:14 AM

But it's already Jul 23, 9:37 AM and the Jul 23, 9:14 AM was not sent. It looks like the mailing service it is working so can you check if it is something related to the the configuration? Please do a good deep dive and fix it.

### ~~Update transaction emails content~~ ✅ COMPLETED
- Once a user signs up I want to send them a transcational email withis content:
Hi [subscriber's first namel],

My name is Manuel, and I'm the founder of MorningNewsletter. Thanks so much for signing up!

If you ever have any questions or just want to share your thoughts, just reply to this email, I read every response.

Manuel

### ~~Update icons on the newsletter cards~~ ✅ COMPLETED
- In the dashboard in the index page where we see all the newsletters cards. Update the icons of the cards to use the new lucide icons. Make sure they look centered with the text. Keep the current colors for those icons, basically jus swap them to lucide icons.

### ~~Update Create New Newsletter box~~ ✅ COMPLETED
- The plus sign button on the right of the times, the first time the Create New Newsletter box opens the icon is not visible. I want to remove the background color of the button, only keep the background color for the hover state. Then the icon color make it green.
Also for the delete time icon, remove the border and only leave the red x icon and Keep the background color for the hover state.
Also the whole Create New Newsletter box make it larger if the user adds more times so it doesn't break.
- Limit the amount of times to 4. Do not allow the user to have more than 4. Hardcode the constant somewhere in constants. Maybe we should have a constant file somewhere in the app that I can update by code.



### ~~Update Newsletter cards on the dashboard task~~ ✅ COMPLETED
- For the newsletter cards, I want to add more button options, like an active/inactive toggle, delete button, duplicate button and then the preview. Can you fit those actions somewhere in the button in a nice way? Maybe have a dropdown?        
- Implemente the duplicate button action                                               
- Rename in the newsletter page the pause newsletter for Active toggle, I think it is more understandable.                                                                 
- Make sure on the dashboard page, the delete, duplicate and active/inactive options work properly for each newsletter card                                                 

### ~~Update Navigation bar task~~ ✅ COMPLETED
- On the navigation bar on the user dropdown, replace the icons for the new lucide icons to match the other ones from the navigation bar.  ✅                               

### ~~Update sending Newsletter format task~~ ✅ COMPLETED
- I want you to update the sending newsletter to have a head line like Good Morning, Good afternoon, Good evening or Good night depending on the hour the newsletter is being sent, check the current timezone and current time of the server to determine the time so the we can decide what to use. This headline will be in bold at the beginning of the email aligned to the left and with a larger font size. 
- Remove the emoji from the title
- I want a second line like a caption saying "It’s Monday, July 21. Here’s what you need to know."  And put there the rigth day. Make sure it works. This caption should also be bold and white but with a smaller font size.
