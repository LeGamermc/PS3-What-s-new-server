import os
import time
print("What's new server installer please sure to have read the README.md before hand")
while(1):
    os.system("clear")
    choice_install = input("Do you wish to (i)nstall or (u)ninstall please enter a option> ")
    if choice_install == "i":
        print("detecting distro...")
        if os.path.exists("/etc/debian_version"):
            print("detecting distro... DEBIAN\nbegining the instalation...")
            os.system("sudo apt update")
            os.system("sudo apt install -y apache2 php")

            os.system("sudo a2enmod cgi")
            os.system("sudo systemctl restart apache2")
        
            os.system("sudo cp -fv apache2-file/000-default.conf /etc/apache2/sites-available/000-default.conf")
            os.system("sudo cp -rfv * /var/www/html")

            os.system("sudo rm -rf /var/www/html/tools\\used")
            os.system("sudo rm -rf /var/www/html/apache2-file")
            print("Done all ready! dont forget to patch your explore_plugin.prx with your url!")
            exit()

        elif os.path.exists("/etc/arch-release"):
            print("detecting distro... ARCH LINUX\nbegining the instalation...")
            os.system("sudo pacman -S --noconfirm apache python php php-apache")

            os.system("sudo sed -i 's/#LoadModule cgid_module/LoadModule cgid_module/' /etc/httpd/conf/httpd.conf")
            os.system("sudo sed -i 's/#LoadModule cgi_module/LoadModule cgi_module/' /etc/httpd/conf/httpd.conf")
            os.system("sudo bash -c 'echo \"LoadModule php_module modules/libphp.so\" >> /etc/httpd/conf/httpd.conf'")
            os.system("sudo bash -c 'echo \"AddHandler php-script .php\" >> /etc/httpd/conf/httpd.conf'")
            os.system("sudo bash -c 'echo \"Include conf/extra/php_module.conf\" >> /etc/httpd/conf/httpd.conf'")
            os.system("sudo systemctl enable --now httpd")
            os.system("sudo systemctl restart httpd")

            os.system("sudo mkdir -p /var/www/html")
            os.system("sudo chown -R http:http /var/www/html")

            os.system("sudo cp -fv apache2-file/000-default-other.conf /etc/httpd/conf.d/000-default.conf")
            os.system("sudo cp -rfv * /var/www/html")

            os.system("sudo rm -rf /var/www/html/tools\\used")
            os.system("sudo rm -rf /var/www/html/apache2-file"
            print("Done all ready! dont forget to patch your explore_plugin.prx with your url!")
            exit()
        
        elif os.path.exists("/etc/fedora-release") or os.path.exists("/etc/redhat-release"):
            print("detecting distro... Fedora/RHEL\nbegining the instalation...")
            os.system("sudo dnf install -y httpd python3 php")

            os.system("sudo systemctl enable --now httpd")
            os.system("sudo systemctl restart httpd")

            os.system("sudo mkdir -p /var/www/html")
            os.system("sudo chown -R apache:apache /var/www/html")

            os.system("sudo cp -fv apache2-file/000-default-other.conf /etc/httpd/conf.d/000-default.conf")
            os.system("sudo cp -rfv * /var/www/html")

            os.system("sudo rm -rf /var/www/html/tools\\used")
            os.system("sudo rm -rf /var/www/html/apache2-file")
            print("Done all ready! dont forget to patch your explore_plugin.prx with your url!")
            exit()
    
        else:
            print("detecting distro... NOT FOUND\nSorry the distro your currently using is not currently supported yet please open a issue on the github citing your distro so i can inplemente it on a future release of this script!")
            time.sleep(4)

    elif choice_install == "u":
        print("starting the removal process...")
        if os.path.exists("/etc/debian_version"):
            os.system("sudo systemctl stop apache2")
            os.system("sudo apt purge -y apache2 php libapache2-mod-php")
            os.system("sudo apt autoremove -y")

        elif os.path.exists("/etc/arch-release"):
            os.system("sudo systemctl stop httpd")
            os.system("sudo pacman -Rns --noconfirm apache php")

        elif os.path.exists("/etc/fedora-release") or os.path.exists("/etc/redhat-release"):
            os.system("sudo systemctl stop httpd")
            os.system("sudo dnf remove -y httpd php")

        os.system("sudo rm -rf /var/www/html")
        print("done!")
        exit()

    else:
        print("Invalid choice")
        time.sleep(2)

