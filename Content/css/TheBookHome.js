const  defimg = 'https://www.thebookhome.com/Content/images/siteDefult.jpg';
const mainpath = 'https://admin.thebookhome.com';
function setUpAutoCompleate() {


    //$.extend($.ui.autocomplete.prototype.options, {
    //    open: function (event, ui) {
    //        $(this).autocomplete("widget").css({
    //            "width": ($(this).width() + "px")
    //        });
    //    }
    //});
   

    var $elem = $('#txtSearch').autocomplete({
        source: function (request, response) {

            $.ajax({
                url: "/home/SearchAutoCompleteNew",
                type: "get",
                dataType: "json",
                minLength: 3,
                data: { word: request.term },
                success: function (data) {
                    response($.map(data,
                        function (item) {
                            return {
                                label: `${item.name}`,
                                value: item.name,
                                id: item.id,
                                type: item.type,
                                typeAr: item.typear,
                                imgurl: item.imgeurl,
                                authid: item.authid,
                                auther: item.auther
                            };
                        }
                    ));
                }
            });
        },
        select: function (event, ui) {
            $('#txtSearchid').val(ui.item.id);
            $('#txtSearchtype').val(ui.item.type);
            $('#txtSearchname').val(ui.item.value);
            $('#txtSearch').val(ui.item.value);
            return false;
        },
        close: function () {
            $('#txtSearch').data('id', '');
            $('#txtSearch').data('type', '');
        }
    });

    var elemAutocomplete = $elem.data("ui-autocomplete") || $elem.data("autocomplete");
    if (elemAutocomplete) {
        elemAutocomplete._renderItem = function (ul, item) {


            var newText = String(item.value).replace(
                new RegExp(this.term, "gi"),
                "<span class='HiLight'>$&</span>");
            if (item.typeAr) {
                newText += `<span class='badge badge-danger'>${item.typeAr}</span>`;
            }
            var url = '';
            var myimg = defimg;
            var itemurl = '';
            var bookauth = '';
            if (item.type == 'Auther') {
                if (item.imgurl) {

                    myimg = "'" + mainpath + "/Content/AutherImg/" + item.imgurl + "'";
                }
                bookauth = `<a class='search-item-author'  href='/home/AuthorDetails/${item.id}'>${item.auther}</a>`;
                itemurl = '/home/AuthorDetails/' + item.id;

            } else if (item.type == 'Books') {
                if (item.imgurl) {
                    myimg = "'" + mainpath + item.imgurl + "'";
                }
                bookauth = `<a class='search-item-author'  href='/home/AuthorDetails/${item.authid}'>${item.auther}</a>`;
                console.log(item);

                itemurl = '/product/index/' + item.id;
            } else if (item.type == 'Pubs') {
                if (item.imgurl) {
                  
                    myimg = "'" + mainpath + "/Content/Pubs/" + item.imgurl +  "'";
                }
                bookauth = `<a class='search-item-author'  href='/home/PublisherDetails/${item.id}'>${item.auther}</a>`;
                itemurl = '/home/PublisherDetails/' + item.id;
            }
            else if (item.type == 'first') {
            
                return $("<li class='ui-menu-item' role='presentation'></li>")
                    .data("item.autocomplete", item)
                    .append(`
                            <li class="result-item" style="">
<button class="btn btn-primary btn-block" type="button" id="mySBtn" onclick="getSearchResult(1, 'Date desc')">
                    <i class="fa fa-search" aria-hidden="true"></i>
عرض جميع نتائج البحث
                    </button>
         
            </li>`).appendTo(ul);
            }
            var src = "<img  style='max-width:55px;min-width:55px;min-height:75px;'   src='" + defimg + "'/>";
            return $("<li class='ui-menu-item' role='presentation'></li>")
                                .data("item.autocomplete", item)
                                .append(`
                            <li class="result-item" style=""><a class="search-item-img" href="${itemurl}">
            <img style='max-width:65px;min-width:45px;min-height:55px; float:left;' src=${myimg} alt=''></a>
            <a class='search-item-title' href='${itemurl}'>${newText}</a>
${bookauth}
            </li>`).appendTo(ul);

                            /*
                             *   <ul class='formats' style='width:auto;background-color: transparent'>
                <li style='display: inline-block;margin: 0 .5rem;padding:0;'>samy</li>
                <li style='display: inline-block;margin: 0 .5rem;padding:0;'>samy2</li>
            </ul>
                             */

            //            } else {

            //return $("<li class='ui-menu-item' role='presentation'></li>")
            //    .data("item.autocomplete", item)
            //    .append('<a href="' + itemurl + '" class="ui-corner-all" tabindex="-1"> ' + src + '<span style="  margin:10px;">' + newText + '</span></a>')
            //    .appendTo(ul);

            //}


            //return $("<li></li>")
            //    .data("item.autocomplete", item)
            //    .append("<a>" + "<img src='" + item.imgsrc + "' />" + item.id + " - " + item.label + "</a>")
            //    .appendTo(ul);
        };
    }
    //jQuery.ui.autocomplete.prototype._resizeMenu = function () {
    //    var ul = this.menu.element;
    //    console.log(ul);
    //    ul.outerWidth(this.element.outerWidth());
    //    console.log(ul.outerWidth() + ">>" + this.element.outerWidth());
    //}
}

function showNotify(id) {
    $.ajax({
        type: 'GET',
        url: '/product/NotAvailableBook',
        data: { bookID:id, isCart: true },
        success: function (data) {
            $('#modal-body').html(data);
            $("#loginModal").modal('show');
        },
        error: function () {
            //faild
            alertify.error("لقد وقع خطأ");
        }
    });
}


$(function () {
    //add to cart
    $(document).on('click', '.addToCart', function (event) {
        //$('.addToCart').click(function (event) {
        var qty = 1;
        if ($('#cartWithValue').val())
            qty = $('#cartWithValue').val();
       
        var bookid = event.currentTarget.id;
        var price = $(this).data('price') //$(`#id-${bookid}-price`).val();
        var name = $(this).data('name') // $(`#id-${bookid}-name`).val();
        var cat = $(this).data('cat') // $(`#id-${bookid}-cat`).val();
        var iswishList = $(this).data('wishList') == 1;// $(`#id-${bookid}-wishList`).val()=="1";
        $("li.item-ii").find("li").css("background-color", "red");
        /*  var fbCatName = $(this).attr('hreflang');*/

        AddToFaceBookCart(bookid, price, name, cat, iswishList);
        $(this).attr('onclick', 'pageTracker._link(this.href); return false;');
        if (event.currentTarget.id) {
            $.ajax({
                type: 'POST',
                url: '/product/AddToFavoritCart',
                data: { bookID: event.currentTarget.id, Qty: qty, isCart: true },
                success: function (data) {
                    if (data.Status == 1) {
                       // facebookAdd(fbBookId, fbPrice, fbCatName);
                        //success     
                        if ($("#cartIcon span").text()) {
                            $("#cartIcon span").text(parseInt($("#cartIcon span").text()) + 1);
                        } else {
                            $("#cartIcon").append("<span>" + 1 + '</span>');
                        }
                        alertify.success(data.Message);
                    } else {
                        //not available
                        if (data.Status == 2) {
                            alertify.error(data.Message);
                        } else if (data.Status == 4) {
                            alertify.warning(data.Message);
                            showNotify(event.currentTarget.id);
                            //$.ajax({
                            //    type: 'GET',
                            //    url: '/product/NotAvailableBook',
                            //    data: { bookID: event.currentTarget.id, isCart: true },
                            //    success: function (data) {
                            //        $('#modal-body').html(data);
                            //        $("#loginModal").modal('show');
                            //    },
                            //    error: function () {
                            //        //faild
                            //        alertify.error("لقد وقع خطأ");
                            //    }
                            //});
                        } else {
                            alertify.warning(data.Message);
                        }
                    }

                },
                error: function () {
                    //faild
                    alertify.error("لقد وقع خطأ");

                }
            });
        }
        return false;
    });

    // add to favorit
    $(document).on('click', '.addToFav', function (event) {
        //$('.addToFav').click(function (event) {
        $(this).attr('onclick', 'pageTracker._link(this.href); return false;');
        if (event.currentTarget.id)
        {
            //data - price="@GetCurrentCost(book, book.PriceAfterDescount ?? 0m)"
            //data - name="@book.BookName"
            //data - cat="@book.AutherName"
            //data - wishList="1" >
            var bookid = event.currentTarget.id;
            var price = $(this).data('price') //$(`#id-${bookid}-price`).val();
            var name = $(this).data('name') // $(`#id-${bookid}-name`).val();
            var cat = $(this).data('cat') // $(`#id-${bookid}-cat`).val();
            var iswishList = $(this).data('wishList') == 1;// $(`#id-${bookid}-wishList`).val()=="1";
            $("li.item-ii").find("li").css("background-color", "red");
            /*  var fbCatName = $(this).attr('hreflang');*/
            
            AddToFaceBookCart(bookid, price, name, cat, iswishList);
          //  facebookAddToFav(fbBookId, fbPrice, fbCatName);
            $.ajax({
                type: 'POST',
                url: '/product/AddToFavoritCart',
                data: { bookID: event.currentTarget.id, Qty: 0, isCart: false },
                success: function (data) {
                    if (data.Status == 1) {
                        //success
                        alertify.success(data.Message);
                    } else {
                        //faild
                        if (data.Status == 2) {
                            alertify.error(data.Message);
                        } else {
                            alertify.warning(data.Message);
                        }
                    }
                },
                error: function () {
                    //faild
                    alertify.error("لقد وقع خطأ");
                }
            });
        }
        return false;
    });

    //remove favorite
    $(document).on('click', '.removeFavorite', function (event) {
        //$('.removeFavorite').click(function (event) {
        $(this).attr('onclick', 'pageTracker._link(this.href); return false;');
        if (event.currentTarget.id) {
            $.ajax({
                type: 'POST',
                url: '/product/RemoveFavorit',
                data: { bookID: event.currentTarget.id },
                success: function (data) {
                    if (data.Status == 1) {
                        //success
                        alertify.success(data.Message);
                        location.reload();
                    } else {
                        //faild
                        alertify.error("لقد وقع خطأ");
                    }
                },
                error: function () {
                    //faild
                    alertify.error("لقد وقع خطأ");
                }
            });
        }
        return false;
    });

    //remove cart
    $(document).on('click', '.removeCart', function (event) {
        //$('.removeCart').click(function (event) {
        $(this).attr('onclick', 'pageTracker._link(this.href); return false;');
        if (event.currentTarget.id) {
            $.ajax({
                type: 'POST',
                url: '/product/RemoveCart',
                data: { bookID: event.currentTarget.id },
                success: function (data) {
                    if (data.Status == 1) {
                        //success
                        alertify.success(data.Message);
                        location.reload();
                    } else {
                        //faild
                        alertify.error("لقد وقع خطأ");
                    }
                },
                error: function () {
                    //faild
                    alertify.error("لقد وقع خطأ");
                }
            });
        }
        return false;
    });

    //logout
    $(document).on('click', '#logOut', function (event) {
        // $('#logOut').click(function (event) {

        $(this).attr('onclick', 'pageTracker._link(this.href); return false;');

        $.ajax({
            type: 'POST',
            url: '/CustomerAccount/LogOut',
            success: function (data) {
                if (data.Status == 1) {
                    //success
                    alertify.success(data.Message);
                    location.reload();
                } else {
                    //faild
                    alertify.error(data.Message);
                }
            },
            error: function () {
                //faild
                alertify.error("لقد وقع خطأ");
            }
        });
        return false;
    });

    //more product
    $(document).on('click', '.moreProdectBn', function (event) {
        //$('.moreProdectBn').click(function (event) {
        
  
      
        if ($(event.target).attr('id') == 'txteventdata') {
            return;
        }
            $(this).attr('onclick', 'pageTracker._link(this.href); return false;');
        window.location.href = '/Home/MoreBooksResult?searchKey=' + event.currentTarget.name + "&bookID=" + event.currentTarget.id;
        return false;
    });
    //more product
    $(document).on('click', '.moreProdectBtnCat', function (event) {
        //$('.moreProdectBtnCat').click(function (event) {
        $(this).attr('onclick', 'pageTracker._link(this.href); return false;');
        window.location.href = '/Home/MoreBooksResultCat/' + event.currentTarget.name + "/" + event.currentTarget.id;
        return false;
    });
    function AddToFaceBookCart(id, price, name, cat, iscart) {
        //console.log(`Id:${id} privce:${price} name:${cat} iscart:${iscart}`);
      
        if (fbq) {
           
            fbq('track',
                iscart ? 'AddToCart' : 'AddToWishlist',
                {
                    value: price,
                    currency: 'EGP',
                    content_name: name,
                    content_type: 'product',
                    content_ids: [`${id}`]
                });

        }
        else {
            console.log("pixel not  ok");
        }

    }
    //-----
    //function facebookAdd(id, price,catName)
    //{
    //    var fbPrice = 0.00;
    //    try {
    //        if (!isNaN(parseInt(price))) {
    //            fbPrice = price;
    //        }
    //        if (!catName || catName.length === 0) {
    //            catName = "Books";
    //        }
    //        fbq('track', 'AddToCart', {
    //            content_name: 'book Added',
    //            content_category: catName,
    //            content_ids: [id],
    //            content_type: 'product',
    //            value: fbPrice,
    //            currency: 'EGP'
    //        });
    //    }
    //    catch(e){
    //        console.error(e);
    //    }
  

       
    //}

    //function facebookAddToFav(id, price, catName) {
    //    var fbPrice = 0.00;
    //    try {

    //        if (!isNaN(parseInt(price))) {
    //            fbPrice =price;
    //        }
    //        if (!catName || catName.length === 0) {
    //            catName = "Books";
    //        }
    //        fbq('track', 'AddToWishlist', {
    //            content_name: 'add to fav',
    //            content_category: catName,
    //            content_ids: [id],
    //            content_type: 'product',
    //            value: fbPrice,
    //            currency: 'EGP'
    //        });
    //    }
    //    catch (e) {
    //        console.error(e);
    //    }


       
    //}
});


    var bttC = document.querySelectorAll(".navAside-m")[0];
    var mobiNav = document.querySelectorAll(".mobi-nav")[0];
    var headNav = document.querySelectorAll(".headNav")[0];
    if (window.innerWidth < 1261) {
        var heightAt = window.innerHeight - headNav.clientHeight;
        
        mobiNav.setAttribute("style", "height: " + heightAt + "px !important; top: " + headNav.clientHeight + "px !important;");
        bttC.addEventListener("click", function () {
            if (mobiNav.hasAttribute("mobiNav")) {
                mobiNav.removeAttribute("mobiNav");
            } else {
                mobiNav.setAttribute("mobiNav", "true");
                var navList = document.querySelectorAll(".nameNaveHeader");
                var navlinkitem = document.querySelectorAll(".nxtLvl");
                for (var ac = 0; ac < navList.length; ac++) {
                    navList[ac].addEventListener("click", function () {
                        if (this.querySelectorAll(".nxtLvl")[0].hasAttribute("navlistlink")) {
                            this.querySelectorAll(".nxtLvl")[0].removeAttribute("navlistlink");
                            this.removeAttribute("mianLinks");
                        } else {
                            for (var acd = 0; acd < navlinkitem.length; acd++) {
                                navlinkitem[acd].removeAttribute("navlistlink");
                            };
                            for (var az = 0; az < navList.length; az++) {
                                navList[az].removeAttribute("mianLinks");
                            };
                            this.querySelectorAll(".nxtLvl")[0].setAttribute("navlistlink", "true");
                            this.setAttribute("mianLinks", "true");

                        }
                    });
                };
            }
        });

    }



//function liveSearch(e = "show") {
//    var t = $("#live-search"); 
//    var n = $("#txtSearch").val();
//    0 !== n.length && "hide" !== e
//        ? (t.fadeIn(),
//            n.length <= 2
//                ? t.html(`<div class="live-search-body">
//                    <span> Enter search terms, at least 3 characters!</span >
//			  </div > `)
//              : $.ajax({
//                        type: "get",
//                     url: "/home/SearchAutoCompleteNew",
//                        data: { search_str: n },
//                        dataType: "html",
//                        beforeSend: function () {
//                            t.html(
//                                `<div class="live-search-body"><div class="book"> <div class="inner"> <div class="left"></div><div class="middle"></div><div class="right"></div></div><ul> <li></li><li></li><li></li><li></li><li></li><li></li>
//                                < li ></li ><li></li><li></li><li></li><li></li><li></li>
//							<li></li><li></li><li></li><li></li><li></li><li></li></ul >
//							</div ></div > `
//                            );
//                        },
//                        success: function (e) {
//                            setTimeout(() => {
//                                t.html(e);
//                            }, 2e3);
//                        },
//                    }))
//                : t.fadeOut();
//}