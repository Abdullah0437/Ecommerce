<?php
/* =========================================================
   ADMIN FOOTER
========================================================= */
?>

        </div><!-- /.admin-content -->

    </main>

</div><!-- /.admin-layout -->


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
    (function () {
        var toggle  = document.getElementById("sidebarToggle");
        var sidebar = document.getElementById("adminSidebar");

        if (toggle && sidebar) {
            toggle.addEventListener("click", function (e) {
                e.stopPropagation();
                sidebar.classList.toggle("open");
            });

            document.addEventListener("click", function (e) {
                if (
                    sidebar.classList.contains("open") &&
                    !sidebar.contains(e.target) &&
                    e.target !== toggle
                ) {
                    sidebar.classList.remove("open");
                }
            });
        }
    })();
</script>

</body>
</html>