$(function () {
  $("#selAll, .delFile").attr("checked", false);

  $("#selAll").change(function () {
    var status = this.checked;

    $("label[for='selAll']").text(
      status === true ? "Alles abwählen" : "Alles auswählen"
    );

    $(".delFile").each(function () {
      this.checked = status;
    });
  });

  $(".delFile, #selAll").change(function () {
    if ($(".delFile").is(":checked")) {
      $("#delSubmit").fadeIn();
    } else {
      $("#delSubmit").fadeOut();
    }
  });

  $("#delForm, #delSubmit").submit(function () {
    return confirm(
      "Möchtest du die ausgewählte(n) Datei(en) wirklick löschen?"
    );
  });

  $(".delete").click(function () {
    return confirm("Möchtest du die augewählte Datei wirklich löschen?");
  });
});
