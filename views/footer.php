    </div> 
    

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>

        document.addEventListener("DOMContentLoaded", function() {
            var activeLink = document.querySelector('.nav-link.active');
            if(activeLink) {
                var title = activeLink.innerText.trim();
                document.getElementById('page-title').innerText = title;
            }
        });
    </script>
</body>
</html>
