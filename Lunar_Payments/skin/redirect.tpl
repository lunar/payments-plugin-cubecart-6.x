<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
  </head>
  <body>
    <script>
      var form = document.createElement("form");
      form.method = "post";
      form.action =
        "index.php?_g=rm&type=gateway&cmd=process&module=Lunar_Payments&orderid={$ORDERID}&transactionid={$TXNID}";
      document.body.appendChild(form);
      form.submit();
    </script>
  </body>
</html>
