#Domain sync explanation

##domain next_due_date synchronization
In configuration file we have four settings,
which have influence on domain's next_due_date synchronization:
 - 'syncUseNativeWHMCS' => true,
 - 'updateNextDueDate'  => true,
 - 'nextDueDateOffset'  => int,
 - 'nextDueDateUpdateMaxDayDifference' => int

First two settings needed to do next_due_date syncronization

NextDueDateOffset make an offset between expirydate and nextduedate in days.
This value can be plus also minus.
To make next due date earlier than expiry date this setting should have plus sign.

NextDueDateUpdateMaxDayDifference is setting that tell to script to get domains that have difference 
between expirydate and nextduedate less than the setting.

### Process

Script find domains by previous settings. Other domains are passed.
Then script set nextduedate on expirydate minus offsetdays value.