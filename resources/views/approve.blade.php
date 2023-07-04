<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <title>Approval</title>
</head>
<body>
<div class="wrapper">
    <div class="content-wrapper">
        <section class="content-header">

        </section>
        <section class="content">
            <div class="container-fluid">
                <button class="btn btn-primary btn-block" data-bs-toggle="modal" data-bs-target="#password_confirmation">Aprroval</button>
            </div>
        </section>
    </div>
</div>
<div class="modal fade" id="password_confirmation" tabindex="-1" aria-labelledby="password-confirmation-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Password Confirmation</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-password-confirmation">
                    <div class="form-group">
                        <label for="Password">Password</label>
                        <input type="password" autofocus class="form-control form-control-border" name="" id="password_user">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</div>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
<script>
    $('#password_confirmation').on('shown.bs.modal', function() {
        $(this).find("input:visible:first").focus();
    });
</script>
</html>