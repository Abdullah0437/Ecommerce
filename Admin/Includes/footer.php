            </div>

            <!-- MAIN CONTENT END -->

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>


<script>

    const sidebarToggle = document.getElementById("sidebarToggle");

    const adminSidebar = document.getElementById("adminSidebar");

    if (sidebarToggle && adminSidebar) {

        sidebarToggle.addEventListener("click", function () {

            adminSidebar.classList.toggle("collapsed");

        });

    }

</script>

</body>

</html>