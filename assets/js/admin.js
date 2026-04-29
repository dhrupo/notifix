(function () {
  function updateProviderSections() {
    var transport = document.querySelector('select[name="rt_notify_settings[transport_driver]"]');

    if (!transport) {
      return;
    }

    document.querySelectorAll("[data-provider-section]").forEach(function (section) {
      var active = section.getAttribute("data-provider-section") === transport.value;
      section.hidden = !active;

      if (active) {
        section.setAttribute("open", "open");
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    updateProviderSections();

    var transport = document.querySelector('select[name="rt_notify_settings[transport_driver]"]');

    if (transport) {
      transport.addEventListener("change", updateProviderSections);
    }
  });
})();
