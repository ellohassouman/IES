import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import Swal from 'sweetalert2';
import { EnumContexte } from '../Enums/enum-contexte';
import { EnumLinkApi } from '../Enums/enum-link-api';
import { EnumRole } from '../Enums/enum-role';
import { PersonneCriteria } from '../Models/personne-criteria.model';
import { PersonneModel } from '../Models/personne-model.model';
import { RequesterService } from './requester.service';

@Injectable({
  providedIn: 'root'
})
export class SecurityService {

  DefaultAvatar='https://s3.eu-central-1.amazonaws.com/bootstrapbaymisc/blog/24_days_bootstrap/fox.jpg'
  PersonneModel : PersonneModel = new PersonneModel()
  EnumRole = EnumRole

  constructor(
    private httpClient : HttpClient,
    private RequesterService : RequesterService,
    private router : Router
    ) { }

    CheckFileExisted(url: string)
    {
      return this.httpClient.get(url).pipe(map(() => true), catchError(() => of(false)));
    }

    async MakeAuth(Personne : PersonneModel,message = '',ShowSpinner : boolean = true)
    {
      if(message!='')
      {
        await this.RequesterService.AsyncGetResponseWithJSON(EnumLinkApi.GetUsersInfoByCriteria,Personne,false,false,message)
      }
      else
      {
        await this.RequesterService.AsyncGetResponseWithJSON(EnumLinkApi.GetUsersInfoByCriteria,Personne,ShowSpinner)
      }
      this.PersonneModel = this.RequesterService.response[0]
      localStorage.setItem('IsAuthMajiid','1')
      localStorage.setItem('UserInfo',JSON.stringify(this.PersonneModel));

      // var hours = 24; // Reset when storage is more than 24hours
      // var now = new Date().getTime();
      // var setupTime = localStorage.getItem('setupTime');
      // if (setupTime == null) {
      //     localStorage.setItem('setupTime', now.toString())
      // } else {
      //     if(now-Number(setupTime) > hours*60*60*1000) {
      //         localStorage.clear()
      //         localStorage.setItem('setupTime', now.toString());
      //     }
      // }
    }

    async Login( Personne : PersonneModel, Redirect : boolean =true)
    {
      var Critere: PersonneCriteria = new PersonneCriteria()
      Critere.Email=Personne.Email
      Critere.MotDePasse=Personne.MotDePasse
      Critere.ForParametrage=0
      await this.RequesterService.AsyncPostResponse(EnumLinkApi.CheckCredentials,Critere,false,false,false)
        if(this.RequesterService.isOk)
        {
          await this.MakeAuth(Personne)
          if(this.RequesterService.isOk && Redirect)
          {
            this.router.navigateByUrl('/Dashboard')
            Swal.close()
          }

        }
        else
        {
          Swal.fire(

            "Echec d'authentification ",
            'Login ou mot de passe incorrecte',
            'error'

          ).then(async (result) => {
            if (result.isConfirmed) {
            }
          })
        }

    }

    Logout()
    {
      Swal.fire(
        {
          title : 'Deconnexion ?',
          text : 'Voulez vous deconnecter ?',
          icon : 'question',
          showDenyButton: true,
          confirmButtonText: 'Oui',
          denyButtonText: 'No',
        }

      ).then(async (result) => {
        if (result.isConfirmed) {
          this.RequesterService.AsyncGetResponse(EnumLinkApi.UpdateSessionInfo+this.GetUserInfo().UtilisateurId)
          this.PersonneModel = new PersonneModel
          localStorage.setItem('IsAuthMajiid','0')
          this.router.navigateByUrl('/Authentification')
        }
      })
    }

    CheckAuth()
    {
      this.SessionManger()
      var IsAuth = Number(localStorage.getItem('IsAuthMajiid'))
      return IsAuth
    }

    CurrentLink = ""
    GetCurrentRoute()
    {
      this.CurrentLink =this.router.url
      return this.CurrentLink
    }

    GetUserInfo()
    {
      var personne : PersonneModel =new PersonneModel
      if(this.CheckAuth())
      {
        personne = JSON.parse(localStorage.getItem('UserInfo')!)
        // this.CheckFileExisted(personne.LienImage) ?
        // personne.LienImage : personne.LienImage= this.DefaultAvatar

        personne.LienImage=="" ?  personne.LienImage= this.DefaultAvatar : null

        // await this.RequesterService.AsyncGetResponseImage(EnumLinkApi.GetImage+pers.LienImageApi)
        // var blobresponse :Blob = this.RequesterService.ImageResponse

      }
      return personne
    }

    SessionManger()
    {
      var hours = 24; // Reset when storage is more than 24hours
        var now = new Date().getTime();
        var setupTime = localStorage.getItem('setupTime');
        var StayConnect =false
        if (setupTime == null) {
            localStorage.setItem('setupTime', now.toString())
            StayConnect=true
        } else {
            if(now-Number(setupTime) > hours*60*60*1000) {
                localStorage.clear()
                localStorage.setItem('setupTime', now.toString());
                StayConnect=false;

                // if(!this.RequesterService.GetCurrentRoute().includes('Dashboard'))
                // {
                //   this.router.navigateByUrl('/')
                // }
            }
        }
        return StayConnect
    }


    ApplyModeEditUser()
    {
      return this.RequesterService.GetCurrentRoute().includes('UserInfo') || this.RequesterService.GetCurrentRoute().includes('SubmitForm')
    }


    // async UpdateUserInfos(Image : File)
    // {
    //   this.PersonneFormroup.patchValue({
    //     UserId : this.GetUserInfo().UtilisateurId,
    //   })
    //   var FormData = this.ConverterService.FormGroupToFormData(this.PersonneFormroup.value)
    //   FormData.append('Image',Image)
    //   await this.AsyncPostResponse(EnumLinkApi.UpdateUserInfos,FormData,true,false,false)
    //   this.isOk==1 ? this.MakeAuth("Information modifié avec succès") : null

    //   if(this.isOk==1)
    //   {
    //     return true
    //   }
    //   return false

    // }


}



