Datatrans Plugin for Payment
======
The datatrans plugin provides a payment method using Datatrans for the Drupal payment module.

** Configuration values: **

- ** Merchant-ID: **                  The Merchant ID as provided in your Merchant account on [pilot.datatrans.biz](https://pilot.datatrans.biz)

  *Example:*                          1000000123

- ** Start URL: **                    Sets where to send the payment.

  *Testing:*                          https://pilot.datatrans.biz/upp/jsp/upStart.jsp  
  *Production:*                       https://payment.datatrans.biz/upp/jsp/upStart.jsp  

- ** Request type: **                 Change the request type.

  *Default:*                          Authorization with immediate settlement

- ** Security Settings: **            The security level to use for transactions.

  *Security Level 0:*                 No additional security, do not use for production.  
  *Security Level 1: (minimum)*       Additional control constants are sent with payment messages.  
  *Security Level 2: (recommended)*   Important parameters are digitally signed and sent with payment messages.  

  Values for the security settings can be found on [pilot.datatrans.biz](https://pilot.datatrans.biz/MenuDispatch.jsp?main=3&sub=3#) by navigating to UPP Administration->Security.

** Important information: **

   The URL for the service callbacks as defined in the payment_datatrans.routing.yml.
   Make sure they match the UPP Data found on [pilot.datatrans.biz](https://pilot.datatrans.biz/MenuDispatch.jsp?main=3&sub=0) [UPP Administration->UPP Data]

   example.com**/datatrans/success/**  
   example.com**/datatrans/error/**  
   example.com**/datatrans/cancel/**  

** Additional Information:**

   Useful links:

   - [Credit cards for testing](https://www.datatrans.ch/showcase/test-cc-numbers)
   - [UPP Data](https://pilot.datatrans.biz/MenuDispatch.jsp?main=3&sub=0)
   - [Security Level & HMACs](https://pilot.datatrans.biz/MenuDispatch.jsp?main=3&sub=3#)

   You have to be logged in for the links to work.
