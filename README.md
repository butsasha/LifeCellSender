# LifeCellSender
Lifecell SMS bulk sender

**Send single:**
```
$msg = (new LifeCellSender('login','password','alfaName'))->sendSingle('My message test','mobileNumber');
var_dump($msg->sendMessages());
```
**Send multiple:**
```
$msg = (new LifeCellSender('login','password','alfaName'))->sendSingle('My message test','mobileNumber');
$msg->sendSingle('My message test','mobileNumber'); //SecondMessage
var_dump($msg->sendMessages());
```
**Output will be XML doc for single receiver:**
```
<?xml version="1.0" encoding="UTF-8"?>
<root>
<message>
<service id="bulk" source="alfaname"/>
><to>mobileNumber</to>
<body content-type="text/plain">My message test</body>
</message>
</root>
```
**Output will be XML doc for multiple receivers:**
```
<?xml version="1.0" encoding="UTF-8"?>
<root>
<message>
<service id="bulk" source="alfaname"/>
<to>mobileNumber</to>
<body content-type="text/plain">My message test</body>
</message>
<message>
<service id="bulk" source="alfaname"/>
<to>mobileNumber</to>
<body content-type="text/plain">My message test</body>
</message>
</root>
```
