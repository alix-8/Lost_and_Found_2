## CampusFind
**“Your Campus Lost & Found Hub Where Lost Items Find Their Owners.”**
A PHP + SQLite web application where students or staff can post items they lost or found inside campus. Users can submit details, update their posts, delete them, and mark items as claimed when they are returned to the owner. Admin (Lost and Found Office) can manage the posts, handle reports, and help user find items.

## Team Roles & Responsibilities
| Role | Members | Responsibilities |
|------|---------|------------------|
| **Designing (UI/UX)** | Magdaraog & Bon | • Wireframe<br>• Handle CSS and responsiveness |
| **Coding / Backend Developer** | Bon, Buenafllor, & Magdaraog | • PHP logic<br>• Form and validation |
| **Database Designer** | Santos & Carbonel | • Design and manage the SQLite database |
| **Quality Assurance (QA)** | All Members | • Test all system features for errors<br>• Report bugs and ensure the system runs smoothly before submission |

--------------------------------------------------------------------------------------------------------
**Individual Contributions & Task Breakdown**
​The following tasks were assigned and tracked across the development timeline:
## Backend & Functional Development

| Member | Responsibilities |
|--------|------------------|
| **Bon, Alexandrian O.** | • Project Manager<br>• Established the GitHub folder structure<br>• Developed the Dashboard Page (Admin & User views)<br>• Implemented the Sidebar and “View Details” functionality |
| **Magdaraog, Mel** | • Created initial wireframes<br>• Developed the “My Posts” Page (lost item tracking)<br>• Built submission forms and implemented Photo Upload functionality |
| **Santos, Jay R.** | • Collaborated on the Database Schema<br>• Developed the FAQs Page based on approved logic<br> |
| **Carbonel, Jess Marvin** | • Collaborated on the Database Schema<br>• Sourced and implemented images and icons based on wireframes |

---

## Frontend & UI/UX Design

| Member | Responsibilities |
|--------|------------------|
| **Bon, Alexandrian O.** | • Responsible for Admin and User Interface UI |
| **Buenaflor, Anne Stephanne** | • Modified style.css (Index, Registration, Login)<br>• Developed the About Page |
| **Magdaraog, Mel and Santos, Jay R** | • Styled the input forms |


---

## Documentation

| Member | Responsibilities |
|--------|------------------|
| **Carbonel, Jess** | • Documentation of the project |


**FEATURES:**
- User registration and login (with password hashing)
- Dashboard displaying lost and found items
- Basic form validation and data sanitization (in log-in, log-out and posting items)
- Mobile-First Design
- Responsive design using HTML, CSS, and Bootstrap
## Admin Site:
- Post lost and found items
- Update, and delete items and details functions (CRUD operations)
- Lost and Found Items Feed
- Send notification to a user who posted lost
## User Site:
- Browse feed/dashboard
- Post lost items (A user’s lost item post will notify admin to view their post.)
- Update, and delete items and details functions (only for their posts)

## Instruction on how to run:
-  Download the repo
- input php -S localhost:7000 on the terminal
