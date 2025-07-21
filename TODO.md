# TODOS
List of task for claude cursor to implement.

RULES: 
- For each task, create a commit so then I have the flexibility to revert it if I don't like what I see.
- Check for tasks below and implmement them following the orders, one the task has been complated, mark the task complated in this .md file.
- Keep implemeting these tasks in the project allways doing a good deep dive of the project and thinking really hard to understand what the user wants.
- Do not stop until you've finished implementing these tasks.


## TASKS

### Update Newsletter cards on the dashboard task
- For the newsletter cards, I want to add more button options, like an active/inactive toggle, delete button, duplicate button and then the preview. Can you fit those actions somewhere in the button in a nice way? Maybe have a dropdown?        
- Implemente the duplicate button action                                               
- Rename in the newsletter page the pause newsletter for Active toggle, I think it is more understandable.                                                                  
- Make sure on the dashboard page, the delete, duplicate and active/inactive options work properly for each newsletter card                                                 

### Update Navigation bar task 
- On the navigation bar on the user dropdown, replace the icons for the new lucide icons to match the other ones from the navigation bar.                                 

### Update sending Newsletter format task
- I want you to update the sending newsletter to have a head line like Good Morning, Good afternoon, Good evening or Good night depending on the hour the newsletter is being sent, check the current timezone and current time of the server to determine the time so the we can decide what to use. This headline will be in bold at the beginning of the email aligned to the left and with a larger font size. 
- Remove the emoji from the title
- I want a second line like a caption saying "It’s Monday, July 21. Here’s what you need to know."  And put there the rigth day. Make sure it works. This caption should also be bold and white but with a smaller font size.

### Update transaction emails content
- Once a user signs up I want to send them a transcational email withis content:
Hi [subscriber's first namel],

My name is Manuel, and I'm the founder of MorningNewsletter. Thanks so much for signing up!

If you ever have any questions or just want to share your thoughts, just reply to this email, I read every response.

Manuel

