# TASKS
List of task for claude cursor to implement.

RULES: 
- For each task, create a commit so then I have the flexibility to revert it if I don't like what I see.
- Check for tasks below and implmement them following the orders.
- Once the task has been complated, mark the task complated in this .md file.
- Keep implemeting these tasks in the project allways doing a good deep dive of the project and thinking really hard to understand what the user wants.
- Do not stop until you've finished implementing these tasks.

## TASKS

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

### Update Create New Newsletter box
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
